const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const { Builder, By, Capabilities, until } = require('selenium-webdriver');
const chrome = require('selenium-webdriver/chrome');

const baseUrl = process.env.SELENIUM_BASE_URL || 'http://127.0.0.1:8080/index.php';
const timeoutMs = Number(process.env.SELENIUM_TIMEOUT_MS || 15000);
const artifactsDir = process.env.SELENIUM_ARTIFACTS_DIR || path.join(process.cwd(), 'selenium-artifacts');

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

async function createDriver() {
  const options = new chrome.Options();
  if (process.env.SELENIUM_HEADLESS !== '0') {
    options.addArguments('--headless=new');
  }
  options.addArguments(
    '--disable-gpu',
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

  return driver;
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
    || (/config\/config\.php|app\.allow_install|install\.php/i.test(text)
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

module.exports = {
  By,
  until,
  assert,
  routeUrl,
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
};
