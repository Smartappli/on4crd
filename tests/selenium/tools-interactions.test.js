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
