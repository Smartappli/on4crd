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
  loginAsAdmin,
  requireAdminCredentials,
  runSeleniumPhp,
} = require('./helpers');

const TINY_PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAQAAAAECAYAAACp8Z5+AAAAEklEQVR4nGPQz3/7HxkzkC4AAE5fKKFmq1FQAAAAAElFTkSuQmCC';

function albumCreationState(title) {
  const output = runSeleniumPhp(`
require_once 'app/bootstrap.php';
$title = getenv('SELENIUM_ALBUM_TITLE') ?: '';
$stmt = db()->prepare('SELECT id, member_id, category, subcategory, title, description, is_public, is_featured, publish_requested FROM albums WHERE title = ? ORDER BY id DESC LIMIT 1');
$stmt->execute([$title]);
$album = $stmt->fetch();
$albumId = is_array($album) ? (int) ($album['id'] ?? 0) : 0;
$photoCount = 0;
$photos = [];
if ($albumId > 0) {
    $photoStmt = db()->prepare('SELECT COUNT(*) FROM album_photos WHERE album_id = ?');
    $photoStmt->execute([$albumId]);
    $photoCount = (int) ($photoStmt->fetchColumn() ?: 0);

    $photosStmt = db()->prepare('SELECT id, sort_order, title, caption, file_path FROM album_photos WHERE album_id = ? ORDER BY sort_order ASC, id ASC');
    $photosStmt->execute([$albumId]);
    foreach ($photosStmt->fetchAll() ?: [] as $photo) {
        $photos[] = [
            'id' => (int) ($photo['id'] ?? 0),
            'sort_order' => (int) ($photo['sort_order'] ?? 0),
            'title' => (string) ($photo['title'] ?? ''),
            'caption' => (string) ($photo['caption'] ?? ''),
            'file_path' => (string) ($photo['file_path'] ?? ''),
        ];
    }
}
echo json_encode([
    'id' => $albumId,
    'member_id' => is_array($album) ? (int) ($album['member_id'] ?? 0) : 0,
    'category' => is_array($album) ? (string) ($album['category'] ?? '') : '',
    'subcategory' => is_array($album) ? (string) ($album['subcategory'] ?? '') : '',
    'title' => is_array($album) ? (string) ($album['title'] ?? '') : '',
    'description' => is_array($album) ? (string) ($album['description'] ?? '') : '',
    'is_public' => is_array($album) ? (int) ($album['is_public'] ?? 0) : 0,
    'is_featured' => is_array($album) ? (int) ($album['is_featured'] ?? 0) : 0,
    'publish_requested' => is_array($album) ? (int) ($album['publish_requested'] ?? 0) : 0,
    'photo_count' => $photoCount,
    'photos' => $photos,
], JSON_THROW_ON_ERROR);
`, { SELENIUM_ALBUM_TITLE: title }).trim();

  return JSON.parse(output || '{"id":0,"photo_count":0,"photos":[]}');
}

function albumPhotoCount(albumId) {
  const output = runSeleniumPhp(`
require_once 'app/bootstrap.php';
$albumId = (int) (getenv('SELENIUM_ALBUM_ID') ?: 0);
$stmt = db()->prepare('SELECT COUNT(*) FROM album_photos WHERE album_id = ?');
$stmt->execute([$albumId]);
echo (string) (int) ($stmt->fetchColumn() ?: 0);
`, { SELENIUM_ALBUM_ID: String(albumId) }).trim();

  return Number.parseInt(output, 10) || 0;
}

async function cleanupAlbumByTitle(driver, title) {
  await visit(driver, 'admin_albums');
  const titleInputs = await driver.findElements(By.css('article.article-item input[name="title"]'));
  for (const input of titleInputs) {
    if ((await input.getAttribute('value')) !== title) {
      continue;
    }
    const article = await input.findElement(By.xpath('./ancestor::article[contains(@class,"article-item")]'));
    const deleteForm = await article.findElement(By.xpath('.//form[.//input[@name="action" and @value="delete_album"]]'));
    const deleteButton = await deleteForm.findElement(By.css('button[type="submit"], button:not([type="button"]), input[type="submit"]'));
    await driver.executeScript(`
      window.confirm = () => true;
      const form = arguments[0];
      const submitButton = arguments[1];
      if (typeof form.requestSubmit === 'function') {
        form.requestSubmit(submitButton);
        return;
      }
      form.submit();
    `, deleteForm, deleteButton);
    await waitForDocumentReady(driver);
    await assertNoServerError(driver);
    return;
  }
}

async function submitForm(driver, form) {
  const submitButton = await form.findElement(By.css('button[type="submit"], button:not([type="button"]), input[type="submit"]'));
  await driver.executeScript(`
    const form = arguments[0];
    const submitButton = arguments[1];
    if (typeof form.requestSubmit === 'function') {
      form.requestSubmit(submitButton);
      return;
    }
    submitButton.click();
  `, form, submitButton);
  await waitForDocumentReady(driver);
  await assertNoServerError(driver);
}

async function uploadWizardPhotos(driver, uploadForm, albumId, title) {
  const uploadUrl = routeUrl('admin_albums');
  const result = await driver.executeAsyncScript(`
    const form = arguments[0];
    const uploadUrl = arguments[1];
    const albumId = arguments[2];
    const title = arguments[3];
    const pngBase64 = arguments[4];
    const done = arguments[arguments.length - 1];
    const csrf = form.querySelector('input[name="_csrf"]')?.value || '';
    const bytes = Uint8Array.from(atob(pngBase64), (char) => char.charCodeAt(0));
    const makeFile = (index) => new File(
      [bytes],
      title + '-' + index + '.png',
      { type: 'image/png' }
    );
    const data = new FormData();
    data.append('_csrf', csrf);
    data.append('action', 'upload_photo');
    data.append('album_id', String(albumId));
    data.append('album_wizard', String(albumId));
    data.append('caption', 'Preview Selenium upload.');
    data.append('photos[]', makeFile(1));
    data.append('photos[]', makeFile(2));
    fetch(uploadUrl, {
      method: 'POST',
      body: data,
      credentials: 'same-origin',
      redirect: 'follow'
    }).then(async (response) => ({
      ok: response.ok,
      status: response.status,
      url: response.url,
      body: await response.text()
    })).catch((error) => ({
      ok: false,
      status: 0,
      url: '',
      body: String(error)
    })).then(done);
  `, uploadForm, uploadUrl, albumId, title, TINY_PNG_BASE64);

  assert.equal(result.ok, true, `L upload album doit repondre en succes HTTP, recu ${result.status}: ${String(result.body).slice(0, 240)}`);
  assert.doesNotMatch(
    String(result.body),
    /Une erreur interne|Internal Server Error|HTTP ERROR 500|HTTP ERROR 503|Erreur 503|Service Unavailable|Uploaded image is invalid|Image t[ée]l[ée]vers[ée]e invalide|No photo was imported|Aucune photo/i,
    'La reponse HTML de l upload ne doit pas contenir d erreur serveur.',
  );
}

test('Selenium admin albums: creation multi-etapes, upload massif et preview', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  await withSelenium(t, async (driver) => {
    const title = `selenium-album-${Date.now()}`;
    const albumDescription = 'Album Selenium de regression upload.';
    const expectedPublicAfterFinalize = process.env.SELENIUM_CREATE_PUBLIC_ALBUM === '1' ? 1 : 0;
    let created = false;

    try {
      await loginAsAdmin(driver, credentials.username, credentials.password);
      await visit(driver, 'admin_albums', { focus: 'album-wizard' });

      const wizardForm = await driver.findElement(By.xpath('//section[@id="album-wizard"]//form[.//input[@name="action" and @value="create_album"]]'));
      await wizardForm.findElement(By.css('input[name="title"]')).sendKeys(title);
      const descriptionTextarea = await wizardForm.findElement(By.css('textarea[name="description"]'));
      if (await descriptionTextarea.isDisplayed()) {
        await descriptionTextarea.sendKeys(albumDescription);
      } else {
        const descriptionEditor = await wizardForm.findElement(By.css('.wysiwyg-editor[contenteditable="true"]'));
        await driver.executeScript(`
          const editor = arguments[0];
          const textarea = arguments[1];
          const value = arguments[2];
          editor.innerHTML = '<p>' + value + '</p>';
          textarea.value = editor.innerHTML;
          editor.dispatchEvent(new Event('input', { bubbles: true }));
          textarea.dispatchEvent(new Event('input', { bubbles: true }));
          textarea.dispatchEvent(new Event('change', { bubbles: true }));
        `, descriptionEditor, descriptionTextarea, albumDescription);
      }
      if (process.env.SELENIUM_CREATE_PUBLIC_ALBUM === '1') {
        const publicCheckbox = await wizardForm.findElement(By.css('input[type="checkbox"][name="is_public"]'));
        if (!(await publicCheckbox.isSelected())) {
          await publicCheckbox.click();
        }
      }
      const featuredCheckbox = await wizardForm.findElement(By.css('input[type="checkbox"][name^="album_is_featured"]'));
      assert.equal(await featuredCheckbox.isSelected(), false, 'Le wizard ne doit pas cocher l album a la une par defaut.');
      await driver.executeScript(`
        const checkbox = arguments[0];
        checkbox.checked = true;
        checkbox.dispatchEvent(new Event('input', { bubbles: true }));
        checkbox.dispatchEvent(new Event('change', { bubbles: true }));
      `, featuredCheckbox);
      await submitForm(driver, wizardForm);
      const createdState = albumCreationState(title);
      assert.ok(createdState.id > 0, 'L album cree par l assistant doit exister en base avant l upload.');
      assert.equal(createdState.title, title, 'Le titre cree par l admin doit etre persiste en base.');
      assert.match(createdState.description, /Album Selenium de regression upload/, 'La description creee par l admin doit etre persistee en base.');
      assert.equal(createdState.category, 'general', 'La categorie par defaut du wizard doit etre persistee.');
      assert.equal(createdState.subcategory, '', 'Aucune sous-categorie ne doit etre forcee par le wizard.');
      assert.equal(createdState.is_public, 0, 'Un album cree dans le wizard reste prive avant finalisation.');
      assert.equal(createdState.is_featured, 1, 'Le choix album a la une du wizard doit etre persiste en base.');
      assert.equal(
        createdState.publish_requested,
        expectedPublicAfterFinalize,
        'Le choix public du wizard doit etre stocke dans publish_requested avant finalisation.',
      );
      assert.equal(createdState.photo_count, 0, 'Aucune photo ne doit etre attachee avant l upload.');
      const albumId = createdState.id;
      created = true;

      await driver.wait(until.elementLocated(By.css('#album-wizard-photos-input')), timeoutMs);
      const uploadForm = await driver.findElement(By.xpath('//section[@id="album-wizard"]//form[.//input[@name="action" and @value="upload_photo"]]'));
      await uploadWizardPhotos(driver, uploadForm, albumId, title);

      await driver.wait(
        () => Promise.resolve(albumPhotoCount(albumId) >= 2),
        Math.max(timeoutMs, 30000),
        'Les photos televersees doivent etre enregistrees en base avant la preview.',
      );
      const uploadedState = albumCreationState(title);
      assert.equal(uploadedState.photo_count, 2, 'Les deux photos envoyees doivent etre enregistrees en base.');
      assert.equal(uploadedState.photos.length, 2, 'Le detail DB doit contenir les deux photos uploadees.');
      for (const [index, photo] of uploadedState.photos.entries()) {
        assert.ok(photo.id > 0, 'Chaque photo uploadee doit avoir un identifiant DB.');
        assert.equal(photo.sort_order, index + 1, 'Les photos uploadees doivent recevoir un ordre stable.');
        assert.ok(photo.title.trim().length > 0, 'Chaque photo uploadee doit avoir un titre DB.');
        assert.equal(photo.caption, 'Preview Selenium upload.', 'La legende saisie a l upload doit etre persistee.');
        assert.match(photo.file_path, /^storage\/uploads\/albums\/.+\.png$/i, 'Le chemin de la photo doit pointer vers le stockage albums.');
      }

      await driver.get(`${routeUrl('admin_albums', { album_wizard: albumId, step: 3, focus: 'album-wizard' })}#album-wizard`);
      await waitForDocumentReady(driver);
      await assertNoServerError(driver);
      await driver.wait(until.elementLocated(By.css('#album-wizard .gallery-item')), Math.max(timeoutMs, 30000));
      let previewImages = await driver.findElements(By.css('#album-wizard .gallery-item img'));
      assert.ok(previewImages.length >= 2, 'Les images televersees doivent apparaitre dans la preview de l assistant.');

      if (process.env.SELENIUM_SKIP_PREVIEW_DELETE !== '1') {
        const deleteForm = await driver.findElement(By.css('#album-wizard .gallery-item form'));
        const deletedPhotoId = Number.parseInt(await deleteForm.findElement(By.css('input[name="photo_id"]')).getAttribute('value'), 10);
        await driver.executeScript('window.confirm = () => true;');
        await submitForm(driver, deleteForm);
        previewImages = await driver.findElements(By.css('#album-wizard .gallery-item img'));
        assert.ok(previewImages.length >= 1, 'La suppression en preview ne doit pas supprimer toutes les photos restantes.');
        const afterDeleteState = albumCreationState(title);
        assert.equal(afterDeleteState.photo_count, uploadedState.photo_count - 1, 'La suppression preview doit supprimer exactement une photo en base.');
        assert.equal(
          afterDeleteState.photos.some((photo) => photo.id === deletedPhotoId),
          false,
          'La photo supprimee en preview ne doit plus exister en base.',
        );
      }

      const finalizeForm = await driver.findElement(By.xpath('//section[@id="album-wizard"]//form[.//input[@name="action" and @value="finalize_album_creation"]]'));
      await submitForm(driver, finalizeForm);
      await driver.wait(until.urlContains('route=admin_albums'), timeoutMs);
      await assertNoServerError(driver);
      const finalizedState = albumCreationState(title);
      assert.equal(finalizedState.is_public, expectedPublicAfterFinalize, 'La finalisation doit appliquer le choix public en base.');
      assert.equal(finalizedState.publish_requested, 0, 'La finalisation doit vider publish_requested en base.');
      assert.ok(finalizedState.photo_count >= 1, 'L album finalise doit conserver au moins une photo en base.');
      for (const photo of finalizedState.photos) {
        assert.equal(photo.caption, 'Preview Selenium upload.', 'La finalisation ne doit pas perdre les legendes des photos.');
        assert.match(photo.file_path, /^storage\/uploads\/albums\/.+\.png$/i, 'La finalisation ne doit pas modifier les chemins des photos.');
      }

      const detailLink = await driver.findElement(By.xpath(`//article[contains(@class,"article-item")][.//input[@name="title" and @value="${title}"]]//a[contains(@href,"route=album")]`));
      await driver.get(await detailLink.getAttribute('href'));
      await waitForDocumentReady(driver);
      await assertNoServerError(driver);
      assert.ok((await driver.findElements(By.css('.album-photo-card img'))).length >= 1, 'Un admin doit pouvoir previsualiser un album finalise avec ses images meme s il reste prive.');

      if (process.env.SELENIUM_CREATE_PUBLIC_ALBUM === '1') {
        await driver.get(routeUrl('admin_albums'));
        await waitForDocumentReady(driver);
        const publicLink = await driver.findElement(By.xpath(`//article[contains(@class,"article-item")][.//input[@name="title" and @value="${title}"]]//a[contains(@href,"route=album")]`));
        await driver.get(await publicLink.getAttribute('href'));
        await waitForDocumentReady(driver);
        await assertNoServerError(driver);
        assert.ok((await driver.findElements(By.css('.album-photo-card img'))).length >= 1);
      }
    } finally {
      if (created) {
        await driver.get(routeUrl('admin_albums'));
        await waitForDocumentReady(driver);
        await cleanupAlbumByTitle(driver, title);
      }
    }
  });
});
