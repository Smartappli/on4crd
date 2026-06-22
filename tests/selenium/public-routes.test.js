const test = require('node:test');
const {
  assert,
  routeUrl,
  withSelenium,
  visit,
  waitForDocumentReady,
  assertNoServerError,
  assertPageHasContent,
  pagePlainText,
  skipIfInstallWizard,
} = require('./helpers');

const publicPageRoutes = [
  ['home', {}],
  ['login', {}],
  ['register', {}],
  ['forgot_password', {}],
  ['reset_password', {}],
  ['membership', {}],
  ['donation', {}],
  ['conditions_utilisation', {}],
  ['mentions_legales', {}],
  ['reglement_interieur', {}],
  ['sponsoring', {}],
  ['gdpr', {}],
  ['search', { q: 'radio', source: 'all' }],
  ['news', {}],
  ['articles', {}],
  ['wiki', {}],
  ['albums', {}],
  ['classifieds', {}],
  ['chatbot', {}],
  ['directory', {}],
  ['tools', {}],
  ['committee', {}],
  ['press', {}],
  ['schools', {}],
  ['events', {}],
  ['auctions', {}],
  ['relais', {}],
  ['code_q', {}],
  ['code_cw', {}],
  ['bandplan_on3', {}],
  ['bandplan_on2', {}],
  ['bandplan_harec', {}],
  ['newsletter_public', {}],
  ['newsletter_unsubscribe', { token: 'selenium-invalid-token' }],
  ['errors', {}],
];

for (const [route, query] of publicPageRoutes) {
  test(`Selenium route publique: ${route} rend une page sans erreur serveur`, async (t) => {
    await withSelenium(t, async (driver) => {
      await visit(driver, route, query);
      if (await skipIfInstallWizard(t, driver)) {
        return;
      }
      await assertPageHasContent(driver, route);
    });
  });
}

const discoveryRoutes = [
  ['sitemap.xml', /<urlset|<sitemapindex|urlset|sitemapindex/i],
  ['robots.txt', /User-agent|Sitemap/i],
  ['llms.txt', /ON4CRD|Radio|Club|http/i],
  ['ai-index.json', /"site"|"routes"|"generated_at"|ON4CRD/i],
  ['knowledge-graph.jsonld', /"@context"|"@type"|schema\.org/i],
  ['events_feed', /^\s*(?:<[^>]+>)*\s*\[|extendedProps|title/i],
];

for (const [route, expected] of discoveryRoutes) {
  test(`Selenium route de decouverte/API: ${route} reste lisible`, async (t) => {
    await withSelenium(t, async (driver) => {
      await driver.get(routeUrl(route));
      await waitForDocumentReady(driver);
      await assertNoServerError(driver);
      if (await skipIfInstallWizard(t, driver)) {
        return;
      }
      const source = await driver.getPageSource();
      const text = await pagePlainText(driver);
      assert.match(`${text}\n${source}`, expected, `${route} doit produire une reponse structuree attendue.`);
    });
  });
}

test('Selenium calendrier: export ICS public ne casse pas', async (t) => {
  await withSelenium(t, async (driver) => {
    await visit(driver, 'events');
    await assertNoServerError(driver);
    if (await skipIfInstallWizard(t, driver)) {
      return;
    }
    const result = await driver.executeAsyncScript(`
      const url = arguments[0];
      const done = arguments[arguments.length - 1];
      fetch(url, { credentials: 'same-origin' })
        .then(async (response) => done({
          status: response.status,
          contentType: response.headers.get('content-type') || '',
          text: await response.text()
        }))
        .catch((error) => done({ status: 0, contentType: '', text: String(error) }));
    `, routeUrl('events', { format: 'ics' }));

    assert.strictEqual(result.status, 200, `Export ICS inaccessible: ${result.text}`);
    assert.match(result.contentType, /text\/calendar/i);
    assert.match(result.text, /BEGIN:VCALENDAR|VCALENDAR|agenda .*disponible|calendar .*available|evenements|events/i);
  });
});

test('Selenium API outils: geocodage sans requete renvoie une erreur JSON controlee', async (t) => {
  await withSelenium(t, async (driver) => {
    await driver.get(routeUrl('tools_geocode'));
    await waitForDocumentReady(driver);
    await assertNoServerError(driver);
    if (await skipIfInstallWizard(t, driver)) {
      return;
    }
    const text = await pagePlainText(driver);
    assert.match(text, /"ok"\s*:\s*false|missing|requete|query|q/i);
  });
});

test('Selenium route inconnue: la page 404 est rendue sans erreur serveur', async (t) => {
  await withSelenium(t, async (driver) => {
    await visit(driver, 'selenium_route_inconnue');
    if (await skipIfInstallWizard(t, driver)) {
      return;
    }
    const text = await pagePlainText(driver);
    assert.match(text, /404|introuvable|not found/i);
  });
});
