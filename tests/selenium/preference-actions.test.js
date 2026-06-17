const test = require('node:test');
const {
  By,
  assert,
  routeUrl,
  withSelenium,
  visit,
  skipIfInstallWizard,
} = require('./helpers');

async function csrfToken(driver) {
  return driver.findElement(By.css('input[name="_csrf"]')).getAttribute('value');
}

async function postFormData(driver, url, fields) {
  return driver.executeScript(`
    const form = new FormData();
    for (const [key, value] of Object.entries(arguments[1])) {
      form.append(key, value);
    }
    return fetch(arguments[0], {
      method: 'POST',
      body: form,
      redirect: 'manual',
      credentials: 'same-origin'
    }).then((response) => ({
      ok: true,
      status: response.status,
      type: response.type,
      url: response.url
    })).catch((error) => ({
      ok: false,
      error: String(error)
    }));
  `, url, fields);
}

test('Selenium preferences: la langue peut etre changee sans erreur serveur', async (t) => {
  await withSelenium(t, async (driver) => {
    await visit(driver, 'home');
    if (await skipIfInstallWizard(t, driver)) {
      return;
    }

    const token = await csrfToken(driver);
    const response = await postFormData(driver, routeUrl('set_language'), {
      _csrf: token,
      locale: 'en',
      return_route: 'home',
    });
    assert.equal(response.ok, true, response.error || 'Requete fetch echouee.');
    assert.ok(
      response.type === 'opaqueredirect' || (response.status >= 200 && response.status < 400),
      `Reponse inattendue pour set_language: ${JSON.stringify(response)}.`,
    );

    await visit(driver, 'home');
    const htmlLang = await driver.executeScript('return document.documentElement.lang || "";');
    assert.match(htmlLang, /^en\b/i);

    const resetToken = await csrfToken(driver);
    await postFormData(driver, routeUrl('set_language'), {
      _csrf: resetToken,
      locale: 'fr',
      return_route: 'home',
    });
  });
});

test('Selenium preferences: theme et accent persistent via cookie/session', async (t) => {
  await withSelenium(t, async (driver) => {
    await visit(driver, 'home');
    if (await skipIfInstallWizard(t, driver)) {
      return;
    }

    let token = await csrfToken(driver);
    let response = await postFormData(driver, routeUrl('set_theme'), {
      _csrf: token,
      theme: 'light',
      return_route: 'home',
    });
    assert.equal(response.ok, true, response.error || 'Changement de theme echoue.');

    await visit(driver, 'home');
    const theme = await driver.executeScript('return document.documentElement.dataset.theme || "";');
    assert.equal(theme, 'light');

    token = await csrfToken(driver);
    response = await postFormData(driver, routeUrl('set_accent'), {
      _csrf: token,
      accent: 'emerald',
      return_route: 'home',
    });
    assert.equal(response.ok, true, response.error || 'Changement d accent echoue.');

    await visit(driver, 'home');
    const accentCookie = await driver.executeScript(`
      return document.cookie.split(';').map((part) => part.trim()).find((part) => part.startsWith('on4crd_accent=')) || '';
    `);
    assert.match(accentCookie, /on4crd_accent=emerald/);

    token = await csrfToken(driver);
    response = await postFormData(driver, routeUrl('toggle_theme'), {
      _csrf: token,
      return_route: 'home',
    });
    assert.equal(response.ok, true, response.error || 'Bascule de theme echouee.');

    await visit(driver, 'home');
    const toggledTheme = await driver.executeScript('return document.documentElement.dataset.theme || "";');
    assert.equal(toggledTheme, 'dark');
  });
});
