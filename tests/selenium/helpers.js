const assert = require('node:assert/strict');
const childProcess = require('node:child_process');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const { Builder, By, Capabilities, until } = require('selenium-webdriver');
const chrome = require('selenium-webdriver/chrome');

function loadLocalSeleniumEnv() {
  const envPath = path.join(process.cwd(), 'storage', 'auth', 'selenium-admin.env');
  if (!fs.existsSync(envPath)) {
    return;
  }

  const lines = fs.readFileSync(envPath, 'utf8').split(/\r?\n/);
  for (const line of lines) {
    const trimmed = line.trim();
    if (trimmed === '' || trimmed.startsWith('#') || !trimmed.includes('=')) {
      continue;
    }

    const separator = trimmed.indexOf('=');
    const key = trimmed.slice(0, separator).trim();
    const value = trimmed.slice(separator + 1).trim();
    if (key !== '' && process.env[key] === undefined) {
      process.env[key] = value;
    }
  }
}

loadLocalSeleniumEnv();

const baseUrl = process.env.SELENIUM_BASE_URL || 'http://127.0.0.1:8080/index.php';
const timeoutMs = Number(process.env.SELENIUM_TIMEOUT_MS || 15000);
const artifactsDir = process.env.SELENIUM_ARTIFACTS_DIR || path.join(process.cwd(), 'selenium-artifacts');
let seleniumFixturesSeeded = false;

function routeUrl(route, query = {}) {
  const url = new URL(baseUrl);
  if (!url.pathname || url.pathname.endsWith('/')) {
    url.pathname = `${url.pathname.replace(/\/$/, '')}/index.php`;
  }
  url.search = '';
  url.searchParams.set('route', route);
  for (const [key, value] of Object.entries(query)) {
    if (value !== undefined && value !== null && value !== '') {
      url.searchParams.set(key, String(value));
    }
  }

  return url.toString();
}

function baseUrlHost() {
  return new URL(baseUrl).host;
}

async function isOutsideBaseUrl(driver) {
  try {
    const current = new URL(await driver.getCurrentUrl());
    return current.host !== baseUrlHost();
  } catch {
    return false;
  }
}

async function createDriver() {
  const options = new chrome.Options();
  if (process.env.SELENIUM_HEADLESS !== '0') {
    options.addArguments('--headless=new');
  }
  options.addArguments(
    '--disable-gpu',
    '--disable-background-networking',
    '--disable-dev-shm-usage',
    '--no-sandbox',
    '--window-size=1440,1200',
  );
  if (process.env.SELENIUM_CHROME_BINARY) {
    options.setChromeBinaryPath(process.env.SELENIUM_CHROME_BINARY);
  }
  const capabilities = Capabilities.chrome().setPageLoadStrategy('eager');

  const driver = await new Builder().forBrowser('chrome').withCapabilities(capabilities).setChromeOptions(options).build();
  await driver.manage().setTimeouts({
    implicit: 0,
    pageLoad: Number(process.env.SELENIUM_PAGELOAD_TIMEOUT_MS || 30000),
    script: timeoutMs,
  });
  await disableServiceWorkerForSelenium(driver);

  return driver;
}

async function disableServiceWorkerForSelenium(driver) {
  if (process.env.SELENIUM_DISABLE_SERVICE_WORKER === '0') {
    return;
  }

  const disableServiceWorkerScript = `
    (() => {
      try {
        Object.defineProperty(Navigator.prototype, 'serviceWorker', {
          configurable: true,
          get: () => undefined
        });
      } catch (error) {}
    })();
  `;

  if (typeof driver.sendDevToolsCommand !== 'function') {
    return;
  }

  try {
    await driver.sendDevToolsCommand('ServiceWorker.disable');
  } catch {
    // Older Chromium/WebDriver builds may not expose the ServiceWorker domain.
  }

  try {
    await driver.sendDevToolsCommand('Page.addScriptToEvaluateOnNewDocument', {
      source: disableServiceWorkerScript,
    });
  } catch {
    // Browser-level service worker disabling above is sufficient when available.
  }
}

async function withSelenium(t, callback) {
  const driver = await createDriver();
  try {
    await callback(driver);
  } catch (error) {
    await saveFailureArtifacts(driver, t.name);
    throw error;
  } finally {
    await driver.quit();
  }
}

async function saveFailureArtifacts(driver, testName) {
  try {
    fs.mkdirSync(artifactsDir, { recursive: true });
    const safeName = testName.replace(/[^a-z0-9._-]+/gi, '-').replace(/^-|-$/g, '').toLowerCase();
    fs.writeFileSync(path.join(artifactsDir, `${safeName}.html`), await driver.getPageSource(), 'utf8');
    fs.writeFileSync(path.join(artifactsDir, `${safeName}.png`), await driver.takeScreenshot(), 'base64');
  } catch {
    // Artifact capture must not hide the original Selenium assertion failure.
  }
}

async function visit(driver, route, query = {}) {
  await driver.get(routeUrl(route, query));
  await waitForDocumentReady(driver);
  await assertNoServerError(driver);
}

async function waitForDocumentReady(driver) {
  await driver.wait(async () => {
    const state = await driver.executeScript('return document.readyState');
    return state === 'complete' || state === 'interactive';
  }, timeoutMs);
}

async function currentBodyText(driver) {
  return driver.findElement(By.css('body')).getText().catch(() => '');
}

async function pagePlainText(driver) {
  const bodyText = await currentBodyText(driver);
  if (bodyText.trim() !== '') {
    return bodyText.replace(/\s+/g, ' ').trim();
  }

  const source = await driver.getPageSource().catch(() => '');
  return source
    .replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi, ' ')
    .replace(/<style\b[^>]*>[\s\S]*?<\/style>/gi, ' ')
    .replace(/<[^>]+>/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

async function pageHasInstallWizard(driver) {
  const text = await pagePlainText(driver);
  return /Assistant de d.{1,2}ploiement ON4CRD/i.test(text)
    || (/config\/config\.php/i.test(text)
      && /base de donn|database|administrateur|administrator|initialisation/i.test(text));
}

async function skipIfInstallWizard(t, driver) {
  if (await pageHasInstallWizard(driver)) {
    t.skip('Instance locale non installee; scenario Selenium ignore.');
    return true;
  }

  return false;
}

async function assertPageHasContent(driver, label = 'page') {
  const source = await driver.getPageSource().catch(() => '');
  const text = await pagePlainText(driver);
  assert.ok(
    text.length > 0 || source.trim().length > 0,
    `${label} doit rendre du contenu visible ou une reponse non vide.`,
  );
}

async function assertNoServerError(driver) {
  const title = await driver.getTitle();
  const source = await driver.getPageSource();
  const bodyText = await currentBodyText(driver);
  const visible = `${title}\n${bodyText}`;
  assert.doesNotMatch(
    visible,
    /Une erreur interne|Internal Server Error|HTTP ERROR 500|HTTP ERROR 503|Erreur 503|\b503\b.*Service Unavailable|Service Unavailable.*\b503\b/i,
  );
  assert.doesNotMatch(
    source,
    /Fatal error|Parse error/i,
  );
}

async function findElements(driverOrElement, selector) {
  return driverOrElement.findElements(By.css(selector));
}

async function firstText(driverOrElement, selector) {
  const elements = await findElements(driverOrElement, selector);
  if (elements.length === 0) {
    return '';
  }

  return elements[0].getText();
}

async function elementExists(driverOrElement, selector) {
  return (await findElements(driverOrElement, selector)).length > 0;
}

async function findFirstExisting(driverOrElement, selectors) {
  for (const selector of selectors) {
    const elements = await findElements(driverOrElement, selector);
    if (elements.length > 0) {
      return elements[0];
    }
  }

  return null;
}

async function isLoginPage(driver) {
  const url = await driver.getCurrentUrl();
  if (/route=login\b/.test(url)) {
    return true;
  }

  if (await elementExists(driver, '[data-login-form]')) {
    return true;
  }

  return await elementExists(driver, 'input[name="callsign"][type="text"]')
    && await elementExists(driver, 'input[name="password"]');
}

async function assertLoginPage(driver, label = 'route protegee') {
  assert.ok(await isLoginPage(driver), `${label} doit rediriger vers la page de connexion.`);
}

function parsePhotoCount(text) {
  const normalized = text.replace(/\s+/g, ' ').trim();
  const beforeLabel = normalized.match(/(\d+)\s+photos?/i);
  if (beforeLabel) {
    return Number(beforeLabel[1]);
  }
  const afterLabel = normalized.match(/photos?\s+(\d+)/i);
  if (afterLabel) {
    return Number(afterLabel[1]);
  }

  return null;
}

async function visibleImageCount(driver, selector) {
  const images = await driver.findElements(By.css(selector));
  let count = 0;
  for (const image of images) {
    const rendered = await driver.executeScript(
      'return arguments[0].complete && arguments[0].naturalWidth > 0 && arguments[0].naturalHeight > 0;',
      image,
    );
    if (rendered) {
      count++;
    }
  }

  return count;
}

async function loginAsAdmin(driver, username, password) {
  resetSeleniumLoginThrottle(username);
  try {
    await driver.manage().deleteAllCookies();
  } catch {
    // A clean cookie jar avoids reusing stale Selenium sessions when a browser profile is recycled.
  }
  await visit(driver, 'login');
  await driver.findElement(By.css('input[name="callsign"]')).sendKeys(username);
  await driver.findElement(By.css('input[name="password"]')).sendKeys(password);

  const captchaLabel = await driver.findElement(By.xpath('//input[@name="captcha"]/ancestor::label')).getText();
  const captchaMatch = captchaLabel.match(/(\d+)\s*\+\s*(\d+)/);
  assert.ok(captchaMatch, `Captcha arithmetique introuvable: ${captchaLabel}`);
  await driver.findElement(By.css('input[name="captcha"]')).sendKeys(String(Number(captchaMatch[1]) + Number(captchaMatch[2])));
  await driver.findElement(By.css('[data-login-form] button')).click();
  await waitForDocumentReady(driver);
  await assertNoServerError(driver);
  await driver.wait(async () => !(await isLoginPage(driver)), timeoutMs, 'Connexion admin Selenium echouee.');
  await assertNoServerError(driver);
}

function resetSeleniumLoginThrottle(username) {
  if (process.env.SELENIUM_RESET_LOGIN_THROTTLE === '0') {
    return;
  }

  runSeleniumPhp(`
require_once 'app/bootstrap.php';
$username = strtoupper(trim((string) (getenv('SELENIUM_THROTTLE_USERNAME') ?: '')));
if ($username !== '' && table_exists('users_throttling')) {
    $bucket = static function (array $criteria): string {
        return rtrim(strtr(base64_encode(hash('sha256', implode("\\n", $criteria), true)), '+/', '-_'), '=');
    };
    $buckets = [
        $bucket(['enumerateUsers', '127.0.0.1']),
        $bucket(['enumerateUsers', '::1']),
        $bucket(['enumerateUsers', '::ffff:127.0.0.1']),
        $bucket(['enumerateUsers', '']),
        $bucket(['attemptToLogin', '127.0.0.1']),
        $bucket(['attemptToLogin', '::1']),
        $bucket(['attemptToLogin', '::ffff:127.0.0.1']),
        $bucket(['attemptToLogin', '']),
        $bucket(['attemptToLogin', 'username', $username]),
    ];
    $placeholders = implode(',', array_fill(0, count($buckets), '?'));
    db()->prepare('DELETE FROM users_throttling WHERE bucket IN (' . $placeholders . ')')->execute($buckets);
}
`, { SELENIUM_THROTTLE_USERNAME: username });
}

function requireAdminCredentials(t) {
  const username = process.env.SELENIUM_ADMIN_USER || process.env.SELENIUM_ADMIN_CALLSIGN || '';
  const password = process.env.SELENIUM_ADMIN_PASSWORD || '';
  if (!username || !password) {
    t.skip('SELENIUM_ADMIN_USER/SELENIUM_ADMIN_PASSWORD non definis; test admin ignore.');
    return null;
  }

  return { username, password };
}

function writeTinyPngFixture(name) {
  const dir = path.join(os.tmpdir(), 'on4crd-selenium-fixtures');
  fs.mkdirSync(dir, { recursive: true });
  const filePath = path.join(dir, name);
  const pngBase64 =
    'iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAYAAABytg0kAAAAGklEQVR4nGP8z8Dwn4GBgYGJgYGB4T8ABQsCBAJH7m4AAAAASUVORK5CYII=';
  fs.writeFileSync(filePath, Buffer.from(pngBase64, 'base64'));

  return filePath;
}

function ensureSeleniumFixtures() {
  if (seleniumFixturesSeeded) {
    return true;
  }

  const seedScript = path.join(process.cwd(), 'tests', 'selenium', 'seed_fixtures.php');
  if (!fs.existsSync(seedScript)) {
    return false;
  }

  const env = { ...process.env };
  if (!env.ON4CRD_CONFIG_FILE) {
    const localConfig = path.join(process.cwd(), 'storage', 'auth', 'selenium-config.php');
    if (!fs.existsSync(localConfig)) {
      return false;
    }
    env.ON4CRD_CONFIG_FILE = localConfig;
  }

  try {
    childProcess.execFileSync('php', ['-d', 'extension=pdo_mysql', seedScript], {
      cwd: process.cwd(),
      env,
      stdio: ['ignore', 'pipe', 'pipe'],
      timeout: 30000,
    });
    seleniumFixturesSeeded = true;
    return true;
  } catch {
    return false;
  }
}

function seleniumPhpEnv(extraEnv = {}) {
  const env = { ...process.env, ...extraEnv };
  if (!env.ON4CRD_CONFIG_FILE) {
    const localConfig = path.join(process.cwd(), 'storage', 'auth', 'selenium-config.php');
    if (fs.existsSync(localConfig)) {
      env.ON4CRD_CONFIG_FILE = localConfig;
    }
  }

  return env;
}

function runSeleniumPhp(source, extraEnv = {}) {
  const phpSource = source.startsWith('<?php') ? source : `<?php\n${source}`;
  return childProcess.execFileSync('php', ['-d', 'extension=pdo_mysql'], {
    cwd: process.cwd(),
    env: seleniumPhpEnv(extraEnv),
    input: phpSource,
    stdio: ['pipe', 'pipe', 'pipe'],
    timeout: 30000,
  }).toString('utf8');
}

module.exports = {
  By,
  until,
  assert,
  routeUrl,
  baseUrlHost,
  isOutsideBaseUrl,
  timeoutMs,
  withSelenium,
  visit,
  waitForDocumentReady,
  currentBodyText,
  pagePlainText,
  pageHasInstallWizard,
  skipIfInstallWizard,
  assertPageHasContent,
  assertNoServerError,
  firstText,
  elementExists,
  findFirstExisting,
  isLoginPage,
  assertLoginPage,
  parsePhotoCount,
  visibleImageCount,
  loginAsAdmin,
  requireAdminCredentials,
  writeTinyPngFixture,
  ensureSeleniumFixtures,
  runSeleniumPhp,
};
