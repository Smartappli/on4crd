const test = require('node:test');
const {
  By,
  until,
  assert,
  timeoutMs,
  withSelenium,
  visit,
  waitForDocumentReady,
  assertNoServerError,
  parsePhotoCount,
  visibleImageCount,
  loginAsAdmin,
  requireAdminCredentials,
  ensureSeleniumFixtures,
} = require('./helpers');

test('Selenium albums: le nombre de photos de la liste correspond au detail public', async (t) => {
  await withSelenium(t, async (driver) => {
    ensureSeleniumFixtures();
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
    ensureSeleniumFixtures();
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
    assert.equal(
      (await driver.findElements(By.css('.album-photo-card figcaption'))).length,
      0,
      'Le detail public ne doit pas afficher de description sous les photos.',
    );

    const renderedImages = await visibleImageCount(driver, '.album-photo-card img');
    assert.equal(renderedImages, expectedOnPage, 'Chaque carte photo doit contenir une image chargee.');
  });
});

test('Selenium album detail: une miniature ouvre la photo agrandie avec sa description', async (t) => {
  await withSelenium(t, async (driver) => {
    ensureSeleniumFixtures();
    await visit(driver, 'albums', { q: 'Selenium fixture album public' });
    const detailLinks = await driver.findElements(By.xpath('//article[contains(@class,"album-tile")]//h2/a[contains(normalize-space(.), "Selenium fixture album public")]'));
    if (detailLinks.length === 0) {
      t.skip('Album fixture public indisponible sur cet environnement.');
      return;
    }

    await detailLinks[0].click();
    await waitForDocumentReady(driver);
    await assertNoServerError(driver);

    const photoLinks = await driver.findElements(By.css('.album-photo-card [data-album-viewer-open]'));
    assert.ok(photoLinks.length >= 1, 'La fiche album doit exposer les miniatures ouvrables.');

    await photoLinks[0].click();
    const viewer = await driver.wait(until.elementLocated(By.css('#album-photo-viewer[open]')), timeoutMs);
    const image = await viewer.findElement(By.css('[data-album-viewer-image]'));
    await driver.wait(async () => {
      const src = await image.getAttribute('src');
      return src.includes('/storage/uploads/albums/');
    }, timeoutMs);

    const copyText = await viewer.findElement(By.css('.album-photo-viewer-copy')).getText();
    assert.match(copyText, /Album public de regression Selenium/i, 'La description de l album doit apparaitre a cote de la photo agrandie.');
    assert.match(copyText, /Selenium fixture photo|Photo de regression Selenium/i, 'Le titre ou la legende de la photo doit rester disponible dans le viewer.');
  });
});

test('Selenium home: la galerie affiche une seule image sans texte', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  await withSelenium(t, async (driver) => {
    ensureSeleniumFixtures();
    await loginAsAdmin(driver, credentials.username, credentials.password);
    await visit(driver, 'home');

    const slides = await driver.findElements(By.css('.home-gallery-carousel .home-gallery-slide'));
    assert.equal(slides.length, 1, 'La galerie home doit afficher une seule image.');
    assert.equal(
      (await driver.findElements(By.css('.home-gallery-carousel .home-gallery-slide span'))).length,
      0,
      'La galerie home ne doit pas afficher de description sous l image.',
    );
    assert.equal(
      (await driver.findElements(By.css('.home-gallery-carousel .home-gallery-link'))).length,
      0,
      'La galerie home ne doit pas afficher de lien texte.',
    );

    const galleryText = (await driver.findElement(By.css('.home-gallery-carousel')).getText()).trim();
    assert.equal(galleryText, '', 'La galerie home ne doit afficher aucun texte visible.');

    const renderedImages = await visibleImageCount(driver, '.home-gallery-carousel .home-gallery-slide img');
    assert.equal(renderedImages, 1, 'La galerie home doit contenir une image chargee.');

    const layout = await driver.executeScript(() => {
      const carousel = document.querySelector('.home-gallery-carousel');
      const slide = document.querySelector('.home-gallery-carousel .home-gallery-slide');
      const image = document.querySelector('.home-gallery-carousel .home-gallery-slide img');
      if (!(carousel instanceof HTMLElement) || !(slide instanceof HTMLElement) || !(image instanceof HTMLImageElement)) {
        return null;
      }

      const carouselRect = carousel.getBoundingClientRect();
      const imageRect = image.getBoundingClientRect();

      return {
        carouselHeight: carouselRect.height,
        imageHeight: imageRect.height,
        imageWidth: imageRect.width,
        imageObjectFit: window.getComputedStyle(image).objectFit,
        slideDisplay: window.getComputedStyle(slide).display,
      };
    });

    assert.ok(layout, 'La galerie home doit exposer un carousel, une slide et une image.');
    assert.ok(layout.carouselHeight >= 160, `La galerie home doit conserver une hauteur stable, recu ${layout.carouselHeight}.`);
    assert.ok(layout.imageWidth >= 100 && layout.imageHeight >= 100, 'L image de galerie home doit occuper le bloc.');
    assert.equal(layout.imageObjectFit, 'cover', 'L image de galerie home doit remplir le carousel.');
    assert.equal(layout.slideDisplay, 'flex', 'La slide de galerie home doit utiliser le layout carousel.');
  });
});
