const test = require('node:test');
const path = require('node:path');
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
  writeTinyPngFixture,
} = require('./helpers');

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
}

test('Selenium admin albums: creation multi-etapes, upload massif et preview', async (t) => {
  const credentials = requireAdminCredentials(t);
  if (credentials === null) {
    return;
  }

  await withSelenium(t, async (driver) => {
    const title = `selenium-album-${Date.now()}`;
    let created = false;

    try {
      await loginAsAdmin(driver, credentials.username, credentials.password);
      await visit(driver, 'admin_albums', { focus: 'album-wizard' });

      const wizardForm = await driver.findElement(By.xpath('//section[@id="album-wizard"]//form[.//input[@name="action" and @value="create_album"]]'));
      await wizardForm.findElement(By.css('input[name="title"]')).sendKeys(title);
      const descriptionTextarea = await wizardForm.findElement(By.css('textarea[name="description"]'));
      if (await descriptionTextarea.isDisplayed()) {
        await descriptionTextarea.sendKeys('Album Selenium de regression upload.');
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
        `, descriptionEditor, descriptionTextarea, 'Album Selenium de regression upload.');
      }
      if (process.env.SELENIUM_CREATE_PUBLIC_ALBUM === '1') {
        const publicCheckbox = await wizardForm.findElement(By.css('input[name="is_public"]'));
        if (!(await publicCheckbox.isSelected())) {
          await publicCheckbox.click();
        }
      }
      await submitForm(driver, wizardForm);
      created = true;

      await driver.wait(until.elementLocated(By.css('#album-wizard-photos-input')), timeoutMs);
      const firstImage = writeTinyPngFixture(`${title}-1.png`);
      const secondImage = writeTinyPngFixture(`${title}-2.png`);
      await driver.findElement(By.css('#album-wizard-photos-input')).sendKeys(`${path.resolve(firstImage)}\n${path.resolve(secondImage)}`);
      const uploadForm = await driver.findElement(By.xpath('//section[@id="album-wizard"]//form[.//input[@name="action" and @value="upload_photo"]]'));
      await submitForm(driver, uploadForm);

      await driver.wait(until.elementLocated(By.css('#album-wizard .gallery-item')), timeoutMs);
      await assertNoServerError(driver);
      let previewImages = await driver.findElements(By.css('#album-wizard .gallery-item img'));
      assert.ok(previewImages.length >= 2, 'Les images televersees doivent apparaitre dans la preview de l assistant.');

        if (process.env.SELENIUM_SKIP_PREVIEW_DELETE !== '1') {
          const deleteForm = await driver.findElement(By.css('#album-wizard .gallery-item form'));
          await driver.executeScript('window.confirm = () => true;');
          await submitForm(driver, deleteForm);
          await waitForDocumentReady(driver);
          await assertNoServerError(driver);
          previewImages = await driver.findElements(By.css('#album-wizard .gallery-item img'));
        assert.ok(previewImages.length >= 1, 'La suppression en preview ne doit pas supprimer toutes les photos restantes.');
      }

      const finalizeForm = await driver.findElement(By.xpath('//section[@id="album-wizard"]//form[.//input[@name="action" and @value="finalize_album_creation"]]'));
      await submitForm(driver, finalizeForm);
      await driver.wait(until.urlContains('route=admin_albums'), timeoutMs);
      await assertNoServerError(driver);

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
