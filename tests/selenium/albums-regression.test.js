const test = require('node:test');
const {
  By,
  assert,
  withSelenium,
  visit,
  waitForDocumentReady,
  assertNoServerError,
  parsePhotoCount,
  visibleImageCount,
} = require('./helpers');

test('Selenium albums: le nombre de photos de la liste correspond au detail public', async (t) => {
  await withSelenium(t, async (driver) => {
    await visit(driver, 'albums');
    const tiles = await driver.findElements(By.css('.album-tile'));
    if (tiles.length === 0) {
      t.skip('Aucun album public disponible sur cet environnement.');
      return;
    }

    const firstTile = tiles[0];
    const tileText = await firstTile.getText();
    const tilePhotoCount = parsePhotoCount(tileText);
    assert.notEqual(tilePhotoCount, null, `Nombre de photos introuvable dans la tuile: ${tileText}`);

    const detailLink = await firstTile.findElement(By.css('.album-tile-media')).getAttribute('href');
    await driver.get(detailLink);
    await waitForDocumentReady(driver);
    await assertNoServerError(driver);

    const statsText = await driver.findElement(By.css('.album-detail-stats')).getText();
    const detailPhotoCount = parsePhotoCount(statsText);
    assert.notEqual(detailPhotoCount, null, `Nombre de photos introuvable dans le detail: ${statsText}`);
    assert.equal(tilePhotoCount, detailPhotoCount, 'La liste publique et le detail album doivent compter les memes photos.');
  });
});

test('Selenium album detail: les photos comptees sont rendues en cartes image', async (t) => {
  await withSelenium(t, async (driver) => {
    await visit(driver, 'albums');
    const links = await driver.findElements(By.css('.album-tile-media'));
    if (links.length === 0) {
      t.skip('Aucun album public disponible sur cet environnement.');
      return;
    }

    await driver.get(await links[0].getAttribute('href'));
    await waitForDocumentReady(driver);
    await assertNoServerError(driver);

    const statsText = await driver.findElement(By.css('.album-detail-stats')).getText();
    const photoCount = parsePhotoCount(statsText) || 0;
    if (photoCount === 0) {
      t.skip('Le premier album public ne contient pas de photo.');
      return;
    }

    const expectedOnPage = Math.min(photoCount, 24);
    const cards = await driver.findElements(By.css('.album-photo-card'));
    assert.equal(cards.length, expectedOnPage, 'Chaque photo paginee doit produire une carte dans la grille.');

    const renderedImages = await visibleImageCount(driver, '.album-photo-card img');
    assert.equal(renderedImages, expectedOnPage, 'Chaque carte photo doit contenir une image chargee.');
  });
});
