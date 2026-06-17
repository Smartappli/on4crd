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
  await driver.get(`${routeUrl('tools')}#${toolId}`);
  await waitForDocumentReady(driver);
  await assertNoServerError(driver);
  if (await skipIfInstallWizard(t, driver)) {
    return false;
  }
  await driver.wait(until.elementLocated(By.css(`#${toolId}`)), timeoutMs);
  return true;
}

async function fillAndWaitText(driver, inputSelector, value, outputSelector, matcher) {
  const input = await driver.findElement(By.css(inputSelector));
  await input.clear();
  await input.sendKeys(value);
  await driver.wait(async () => {
    const text = await driver.findElement(By.css(outputSelector)).getText();
    return matcher.test(text);
  }, timeoutMs);
}

async function setSelectValue(driver, selector, value) {
  await driver.executeScript(
    'const select = document.querySelector(arguments[0]); select.value = arguments[1]; select.dispatchEvent(new Event("change", { bubbles: true }));',
    selector,
    value,
  );
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

test('Selenium outils: frequence vers longueur onde', async (t) => {
  await withSelenium(t, async (driver) => {
    if (!(await openTool(driver, t, 'tool-freq-wave'))) {
      return;
    }
    await fillAndWaitText(driver, '#freq-mhz', '145.5', '#freq-wavelength', /2\.060.*m/);
  });
});

test('Selenium outils: puissance W/dBm', async (t) => {
  await withSelenium(t, async (driver) => {
    if (!(await openTool(driver, t, 'tool-power'))) {
      return;
    }
    await fillAndWaitText(driver, '#power-watts', '10', '#power-dbm', /40\.00/);
    await fillAndWaitText(driver, '#power-dbm-input', '40', '#power-watts-out', /10\.0000/);
  });
});

test('Selenium outils: conversion unites RF', async (t) => {
  await withSelenium(t, async (driver) => {
    if (!(await openTool(driver, t, 'tool-unit-converter'))) {
      return;
    }

    await setSelectValue(driver, '#unit-conv-group', 'rf');
    await setSelectValue(driver, '#unit-conv-from', 'mhz');
    await setSelectValue(driver, '#unit-conv-to', 'khz');
    await fillAndWaitText(driver, '#unit-conv-input', '145.5', '#unit-conv-output', /145[,\s.]?500.*kHz/);
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
    assert.match(text, /erreur|error|tool/i);
  });
});
