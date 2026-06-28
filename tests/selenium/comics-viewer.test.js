const test = require('node:test');
const {
  By,
  assert,
  withSelenium,
  visit,
  skipIfInstallWizard,
  timeoutMs,
} = require('./helpers');

test('Selenium comics: la visionneuse ouvre la planche A4 pleine resolution', async (t) => {
  await withSelenium(t, async (driver) => {
    await visit(driver, 'comics', { lang: 'fr' });
    if (await skipIfInstallWizard(t, driver)) {
      return;
    }

    const firstTrigger = await driver.findElement(By.css('[data-comics-viewer-open]'));
    const thumbSrc = await firstTrigger.findElement(By.css('img')).getAttribute('src');
    assert.ok(thumbSrc.includes('-thumb.jpg'), 'La galerie doit afficher la miniature JPEG.');

    const relatedDocument = await driver.findElement(By.css('.comics-related-document[href*="loi-ohm-fiche-memo.md"]'));
    const relatedDocumentText = await relatedDocument.getText();
    assert.match(relatedDocumentText, /Fiche|m[eé]mo/i, 'La planche Ohm doit exposer son document connexe.');
    assert.ok((await relatedDocument.getAttribute('download')).endsWith('loi-ohm-fiche-memo.md'), 'Le document connexe local doit rester telechargeable.');

    await driver.executeScript('arguments[0].scrollIntoView({ block: "center" });', firstTrigger);
    await firstTrigger.click();

    const dialog = await driver.findElement(By.css('[data-comics-viewer]'));
    await driver.wait(async () => (await dialog.getAttribute('open')) !== null, timeoutMs);

    const viewerImage = await dialog.findElement(By.css('[data-comics-viewer-image]'));
    const fullSrc = await viewerImage.getAttribute('src');
    assert.ok(fullSrc.includes('/assets/comics/'), 'La visionneuse doit charger une planche depuis assets/comics.');
    assert.ok(fullSrc.includes('.png'), 'La visionneuse doit charger le PNG pleine resolution.');
    assert.ok(!fullSrc.includes('-thumb.jpg'), 'La visionneuse ne doit pas reutiliser la miniature.');

    const title = await dialog.findElement(By.css('[data-comics-viewer-title]')).getText();
    assert.ok(title.trim().length > 0, 'La visionneuse doit exposer le titre de la planche.');

    const download = await dialog.findElement(By.css('[data-comics-viewer-download]'));
    assert.ok((await download.getAttribute('href')).includes('.png'), 'Le lien de telechargement doit pointer vers le PNG.');
    assert.ok((await download.getAttribute('download')).endsWith('.png'), 'Le nom de telechargement doit rester un PNG.');

    await dialog.findElement(By.css('[data-comics-viewer-close]')).click();
    await driver.wait(async () => (await dialog.getAttribute('open')) === null, timeoutMs);
  });
});
