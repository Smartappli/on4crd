const test = require('node:test');
const {
  By,
  assert,
  withSelenium,
  visit,
  assertPageHasContent,
  skipIfInstallWizard,
  elementExists,
} = require('./helpers');

const layoutRoutes = [
  ['home', {}],
  ['news', {}],
  ['articles', {}],
  ['wiki', {}],
  ['albums', {}],
  ['classifieds', {}],
  ['tools', {}],
  ['events', {}],
  ['auctions', {}],
  ['membership', {}],
  ['donation', {}],
  ['conditions_utilisation', {}],
  ['mentions_legales', {}],
  ['reglement_interieur', {}],
  ['sponsoring', {}],
  ['gdpr', {}],
  ['newsletter_public', {}],
  ['newsletter_unsubscribe', { token: 'selenium-invalid-token' }],
  ['chatbot', {}],
  ['directory', {}],
  ['committee', {}],
  ['press', {}],
  ['schools', {}],
  ['comics', {}],
  ['relais', {}],
  ['code_q', {}],
  ['code_cw', {}],
  ['bandplan_on3', {}],
  ['bandplan_on2', {}],
  ['bandplan_harec', {}],
  ['search', { q: 'radio', source: 'all' }],
];

for (const [route, query] of layoutRoutes) {
  test(`Selenium layout/SEO: ${route} expose la structure publique minimale`, async (t) => {
    await withSelenium(t, async (driver) => {
      await visit(driver, route, query);
      if (await skipIfInstallWizard(t, driver)) {
        return;
      }

      await assertPageHasContent(driver, route);
      assert.ok(await elementExists(driver, '#main-content'), `${route} doit exposer #main-content.`);
      assert.ok(await elementExists(driver, '.topbar'), `${route} doit exposer l entete.`);
      assert.ok(await elementExists(driver, '.site-footer'), `${route} doit exposer le pied de page.`);

      const title = await driver.getTitle();
      assert.ok(title.trim().length > 0, `${route} doit avoir un titre HTML.`);

      const metaDescription = await driver.executeScript(
        'return document.querySelector("meta[name=description]")?.getAttribute("content") || "";',
      );
      assert.ok(metaDescription.trim().length > 0, `${route} doit avoir une meta description.`);

      const canonical = await driver.executeScript(
        'return document.querySelector("link[rel=canonical]")?.getAttribute("href") || "";',
      );
      assert.ok(canonical.trim().length > 0, `${route} doit avoir une URL canonique.`);

      const jsonLdBlocks = await driver.executeScript(
        'return Array.from(document.querySelectorAll("script[type=\\"application/ld+json\\"]")).map((script) => script.textContent || "");',
      );
      for (const block of jsonLdBlocks) {
        assert.doesNotThrow(() => JSON.parse(block), `${route} contient un JSON-LD invalide.`);
      }
    });
  });
}

test('Selenium layout: navigation, preferences, idee et footer sont disponibles sur accueil', async (t) => {
  await withSelenium(t, async (driver) => {
    await visit(driver, 'home');
    if (await skipIfInstallWizard(t, driver)) {
      return;
    }

    const expectedNavFragments = ['route=news', 'route=comics', 'route=events', 'route=tools', 'route=search', 'route=directory'];
    const navHrefs = await driver.executeScript(
      'return Array.from(document.querySelectorAll(".nav a[href]")).map((link) => link.getAttribute("href") || "");',
    );
    for (const fragment of expectedNavFragments) {
      assert.ok(navHrefs.some((href) => href.includes(fragment)), `Lien nav attendu absent: ${fragment}`);
    }

    assert.ok(await elementExists(driver, '[data-idea-modal-open]'));
    assert.ok(await elementExists(driver, '#idea-dialog form input[name="idea_email"]'));
    assert.ok(await elementExists(driver, '#idea-dialog form textarea[name="idea_message"]'));
    assert.ok(await elementExists(driver, '#idea-dialog form input[name="idea_website"]'));

    assert.ok(await elementExists(driver, 'form[action*="set_language"] select[name="locale"]'));
    assert.ok(await elementExists(driver, 'form[action*="set_theme"] select[name="theme"]'));
    assert.ok(await elementExists(driver, 'form[action*="set_accent"] select[name="accent"]'));

    assert.ok(await elementExists(driver, 'form[action*="footer_contact"] input[name="name"]'));
    assert.ok(await elementExists(driver, 'form[action*="footer_contact"] input[name="email"]'));
    assert.ok(await elementExists(driver, 'form[action*="footer_contact"] textarea[name="message"]'));
    assert.ok(await elementExists(driver, 'form[action*="footer_contact"] input[name="contact_captcha"]'));
    assert.ok(await elementExists(driver, 'form[action*="footer_contact"] input[name="contact_website"]'));

    const footerLinks = await driver.findElements(By.css('.site-footer a[href]'));
    assert.ok(footerLinks.length >= 4, 'Le footer doit exposer plusieurs liens utiles.');
  });
});
