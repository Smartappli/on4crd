const test = require('node:test');
const {
  By,
  until,
  assert,
  routeUrl,
  timeoutMs,
  withSelenium,
  visit,
  waitForDocumentReady,
  assertNoServerError,
  pagePlainText,
  skipIfInstallWizard,
} = require('./helpers');

async function openTool(driver, t, toolId) {
  await driver.get(routeUrl('tools', { ajax: 'tool_panel', id: toolId }));
  await waitForDocumentReady(driver);
  await assertNoServerError(driver);
  if (await skipIfInstallWizard(t, driver)) {
    return false;
  }
  await driver.wait(until.elementLocated(By.css(`#${toolId}`)), timeoutMs);
  return true;
}

async function openInteractiveTool(driver, t, toolId) {
  await driver.get(`${routeUrl('tools')}#${toolId}`);
  await waitForDocumentReady(driver);
  await assertNoServerError(driver);
  if (await skipIfInstallWizard(t, driver)) {
    return false;
  }

  await driver.wait(async () => {
    const panels = await driver.findElements(By.css(`#${toolId}[data-tool-panel]`));
    if (panels.length === 0) {
      return false;
    }
    const className = await panels[0].getAttribute('class');
    return !String(className || '').split(/\s+/).includes('is-hidden');
  }, timeoutMs, `${toolId} doit etre charge et visible.`);
  return true;
}

async function setInputValue(driver, selector, value) {
  const element = await driver.findElement(By.css(selector));
  await driver.executeScript(`
    const element = arguments[0];
    element.value = arguments[1];
    element.dispatchEvent(new Event('input', { bubbles: true }));
    element.dispatchEvent(new Event('change', { bubbles: true }));
  `, element, value);
}

async function setSelectValue(driver, selector, value) {
  const element = await driver.findElement(By.css(selector));
  await driver.executeScript(`
    const element = arguments[0];
    element.value = arguments[1];
    element.dispatchEvent(new Event('change', { bubbles: true }));
  `, element, value);
}

async function textContent(driver, selector) {
  return String(await driver.findElement(By.css(selector)).getText()).replace(/\s+/g, ' ').trim();
}

test('Selenium outils: index et chargement AJAX des panneaux fonctionnent', async (t) => {
  await withSelenium(t, async (driver) => {
    await visit(driver, 'tools');
    if (await skipIfInstallWizard(t, driver)) {
      return;
    }
    assert.ok((await driver.findElements(By.css('[data-tool-target]'))).length > 10);
    assert.ok((await driver.findElements(By.css('[data-tool-panel]'))).length >= 1);

    await driver.get(routeUrl('tools', { ajax: 'tool_panel', id: 'tool-freq-wave' }));
    await waitForDocumentReady(driver);
    await assertNoServerError(driver);
    const source = await driver.getPageSource();
    assert.match(source, /id="tool-freq-wave"|data-tool-panel/);
  });
});

test('Selenium outils: panneau frequence vers longueur onde', async (t) => {
  await withSelenium(t, async (driver) => {
    if (!(await openTool(driver, t, 'tool-freq-wave'))) {
      return;
    }
    assert.ok((await driver.findElements(By.css('#freq-mhz'))).length > 0);
    assert.ok((await driver.findElements(By.css('#freq-wavelength'))).length > 0);
  });
});

test('Selenium outils: calcul frequence longueur onde dans le navigateur', async (t) => {
  await withSelenium(t, async (driver) => {
    if (!(await openInteractiveTool(driver, t, 'tool-freq-wave'))) {
      return;
    }

    await setInputValue(driver, '#freq-mhz', '145.5');
    await driver.wait(async () => /2\.061/.test(await textContent(driver, '#freq-wavelength')), timeoutMs);
    assert.match(await textContent(driver, '#freq-wavelength'), /2\.061\s*m/i);

    await setInputValue(driver, '#freq-mhz', '-1');
    assert.match(await textContent(driver, '#freq-wavelength'), /-|—/);
  });
});

test('Selenium outils: panneau puissance W/dBm', async (t) => {
  await withSelenium(t, async (driver) => {
    if (!(await openTool(driver, t, 'tool-power'))) {
      return;
    }
    assert.ok((await driver.findElements(By.css('#power-watts'))).length > 0);
    assert.ok((await driver.findElements(By.css('#power-dbm'))).length > 0);
    assert.ok((await driver.findElements(By.css('#power-dbm-input'))).length > 0);
    assert.ok((await driver.findElements(By.css('#power-watts-out'))).length > 0);
  });
});

test('Selenium outils: conversions puissance W dBm interactives', async (t) => {
  await withSelenium(t, async (driver) => {
    if (!(await openInteractiveTool(driver, t, 'tool-power'))) {
      return;
    }

    await setInputValue(driver, '#power-watts', '5');
    await driver.wait(async () => /36\.99/.test(await textContent(driver, '#power-dbm')), timeoutMs);
    assert.match(await textContent(driver, '#power-dbm'), /36\.99/);

    await setInputValue(driver, '#power-dbm-input', '30');
    await driver.wait(async () => /1\.0000/.test(await textContent(driver, '#power-watts-out')), timeoutMs);
    assert.match(await textContent(driver, '#power-watts-out'), /1\.0000\s*W/i);
  });
});

test('Selenium outils: panneau conversion unites RF', async (t) => {
  await withSelenium(t, async (driver) => {
    if (!(await openTool(driver, t, 'tool-unit-converter'))) {
      return;
    }

    assert.ok((await driver.findElements(By.css('#unit-conv-group option[value="rf"]'))).length > 0);
    const groupsJson = await driver.findElement(By.css('#tool-unit-converter')).getAttribute('data-unit-conv-groups');
    assert.match(groupsJson, /"mhz"/);
    assert.match(groupsJson, /"khz"/);
    assert.ok((await driver.findElements(By.css('#unit-conv-input'))).length > 0);
    assert.ok((await driver.findElements(By.css('#unit-conv-output'))).length > 0);
  });
});

test('Selenium outils: convertisseur unites applique les familles RF et puissance', async (t) => {
  await withSelenium(t, async (driver) => {
    if (!(await openInteractiveTool(driver, t, 'tool-unit-converter'))) {
      return;
    }

    await setInputValue(driver, '#unit-conv-input', '145.5');
    await driver.wait(async () => /145[\s.,]?500\s*kHz/i.test(await textContent(driver, '#unit-conv-output')), timeoutMs);
    assert.match(await textContent(driver, '#unit-conv-output'), /145[\s.,]?500\s*kHz/i);

    await setSelectValue(driver, '#unit-conv-group', 'power');
    await setInputValue(driver, '#unit-conv-input', '5');
    await driver.wait(async () => /36\.989|36\.99/.test(await textContent(driver, '#unit-conv-output')), timeoutMs);
    assert.match(await textContent(driver, '#unit-conv-output'), /36\.989|36\.99/);

    const reference = await textContent(driver, '#unit-conv-reference');
    assert.match(reference, /5\s*W/i);
    assert.match(reference, /dBm/i);
  });
});

test('Selenium outils: panneaux radio avances calculent des resultats', async (t) => {
  await withSelenium(t, async (driver) => {
    if (!(await openInteractiveTool(driver, t, 'tool-dbsum'))) {
      return;
    }
    await setInputValue(driver, '#dbsum-a', '30');
    await setInputValue(driver, '#dbsum-b', '30');
    await driver.wait(async () => /33\.01\s*dBm/i.test(await textContent(driver, '#dbsum-result')), timeoutMs);
    assert.match(await textContent(driver, '#dbsum-result'), /33\.01\s*dBm/i);

    if (!(await openInteractiveTool(driver, t, 'tool-dbuv'))) {
      return;
    }
    await setInputValue(driver, '#dbuv-dbm', '-73');
    await driver.wait(async () => /34\.00/i.test(await textContent(driver, '#dbuv-result')), timeoutMs);
    assert.match(await textContent(driver, '#dbuv-result'), /34\.00/i);

    if (!(await openInteractiveTool(driver, t, 'tool-gain-conv'))) {
      return;
    }
    await setInputValue(driver, '#gain-dbd', '3');
    await driver.wait(async () => /5\.15\s*dBi/i.test(await textContent(driver, '#gain-dbi')), timeoutMs);
    assert.match(await textContent(driver, '#gain-dbi'), /5\.15\s*dBi/i);
  });
});

test('Selenium outils: panneau inconnu AJAX renvoie une erreur controlee', async (t) => {
  await withSelenium(t, async (driver) => {
    await driver.get(routeUrl('tools', { ajax: 'tool_panel', id: 'tool-inconnu-selenium' }));
    await waitForDocumentReady(driver);
    await assertNoServerError(driver);
    if (await skipIfInstallWizard(t, driver)) {
      return;
    }
    const text = await pagePlainText(driver);
    assert.match(text, /erreur|error|outil|tool|impossible/i);
  });
});
