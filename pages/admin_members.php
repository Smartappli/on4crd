<?php
declare(strict_types=1);

require_permission('admin.access');
$locale = current_locale();
$i18n = [
    'fr' => ['member_updated' => 'Membre mis à jour.', 'title' => 'Gestion des membres', 'layout' => 'Membres', 'meta_desc' => 'Gestion des profils membres.', 'members' => 'Membres', 'th_callsign' => 'Indicatif', 'th_name' => 'Nom', 'th_email' => 'Email', 'th_locator' => 'Locator', 'th_active' => 'Actif', 'th_committee' => 'Comité', 'th_actions' => 'Actions', 'save' => 'Enregistrer', 'err_callsign' => 'Indicatif requis.', 'err_email' => 'Email invalide.', 'err_locator' => 'Locator invalide.', 'search' => 'Rechercher un membre', 'search_ph' => 'Indicatif, nom, email…'],
    'en' => ['member_updated' => 'Member updated.', 'title' => 'Member management', 'layout' => 'Members', 'meta_desc' => 'Manage member profiles.', 'members' => 'Members', 'th_callsign' => 'Callsign', 'th_name' => 'Name', 'th_email' => 'Email', 'th_locator' => 'Locator', 'th_active' => 'Active', 'th_committee' => 'Committee', 'th_actions' => 'Actions', 'save' => 'Save', 'err_callsign' => 'Callsign required.', 'err_email' => 'Invalid email.', 'err_locator' => 'Invalid locator.', 'search' => 'Search member', 'search_ph' => 'Callsign, name, email…'],
    'de' => ['member_updated' => 'Mitglied aktualisiert.', 'title' => 'Mitgliederverwaltung', 'layout' => 'Mitglieder', 'meta_desc' => 'Mitgliederprofile verwalten.', 'members' => 'Mitglieder', 'th_callsign' => 'Rufzeichen', 'th_name' => 'Name', 'th_email' => 'E-Mail', 'th_locator' => 'Locator', 'th_active' => 'Aktiv', 'th_committee' => 'Komitee', 'th_actions' => 'Aktionen', 'save' => 'Speichern', 'err_callsign' => 'Rufzeichen erforderlich.', 'err_email' => 'Ungültige E-Mail.', 'err_locator' => 'Ungültiger Locator.', 'search' => 'Mitglied suchen', 'search_ph' => 'Rufzeichen, Name, E-Mail…'],
    'es' => ['member_updated' => 'Miembro actualizado.', 'title' => 'Gestión de miembros', 'layout' => 'Miembros', 'meta_desc' => 'Gestión de perfiles de miembros.', 'members' => 'Miembros', 'th_callsign' => 'Indicativo', 'th_name' => 'Nombre', 'th_email' => 'Correo', 'th_locator' => 'Locator', 'th_active' => 'Activo', 'th_committee' => 'Comité', 'th_actions' => 'Acciones', 'save' => 'Guardar', 'err_callsign' => 'Indicativo obligatorio.', 'err_email' => 'Correo inválido.', 'err_locator' => 'Locator inválido.', 'search' => 'Buscar miembro', 'search_ph' => 'Indicativo, nombre, correo…'],
    'it' => ['member_updated' => 'Membro aggiornato.', 'title' => 'Gestione membri', 'layout' => 'Membri', 'meta_desc' => 'Gestione profili membri.', 'members' => 'Membri', 'th_callsign' => 'Nominativo', 'th_name' => 'Nome', 'th_email' => 'Email', 'th_locator' => 'Locator', 'th_active' => 'Attivo', 'th_committee' => 'Comitato', 'th_actions' => 'Azioni', 'save' => 'Salva', 'err_callsign' => 'Nominativo obbligatorio.', 'err_email' => 'Email non valida.', 'err_locator' => 'Locator non valido.', 'search' => 'Cerca membro', 'search_ph' => 'Nominativo, nome, email…'],
    'pt' => ['member_updated' => 'Membro atualizado.', 'title' => 'Gestão de membros', 'layout' => 'Membros', 'meta_desc' => 'Gestão de perfis de membros.', 'members' => 'Membros', 'th_callsign' => 'Indicativo', 'th_name' => 'Nome', 'th_email' => 'Email', 'th_locator' => 'Locator', 'th_active' => 'Ativo', 'th_committee' => 'Comissão', 'th_actions' => 'Ações', 'save' => 'Guardar', 'err_callsign' => 'Indicativo obrigatório.', 'err_email' => 'Email inválido.', 'err_locator' => 'Locator inválido.', 'search' => 'Pesquisar membro', 'search_ph' => 'Indicativo, nome, email…'],
    'nl' => ['member_updated' => 'Lid bijgewerkt.', 'title' => 'Ledenbeheer', 'layout' => 'Leden', 'meta_desc' => 'Beheer van ledenprofielen.', 'members' => 'Leden', 'th_callsign' => 'Callsign', 'th_name' => 'Naam', 'th_email' => 'E-mail', 'th_locator' => 'Locator', 'th_active' => 'Actief', 'th_committee' => 'Comité', 'th_actions' => 'Acties', 'save' => 'Opslaan', 'err_callsign' => 'Callsign verplicht.', 'err_email' => 'Ongeldig e-mailadres.', 'err_locator' => 'Ongeldige locator.', 'search' => 'Lid zoeken', 'search_ph' => 'Callsign, naam, e-mail…'],
];
$t = $i18n[$locale] ?? $i18n['fr'];
set_page_meta(['title' => (string) $t['layout'], 'description' => (string) $t['meta_desc'], 'robots' => 'noindex,nofollow']);

$returnQuery = http_build_query(['member_q' => (string) ($_GET['member_q'] ?? ''), 'sort' => (string) ($_GET['sort'] ?? 'callsign'), 'dir' => (string) ($_GET['dir'] ?? 'asc')]);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $callsign = strtoupper(trim((string) ($_POST['callsign'] ?? '')));
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $locator = strtoupper(trim((string) ($_POST['locator'] ?? '')));
    if ($callsign === '') { set_flash('error', (string) $t['err_callsign']); redirect('admin_members'); }
    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) { set_flash('error', (string) $t['err_email']); redirect('admin_members'); }
    if ($locator !== '' && preg_match('/^[A-R]{2}[0-9]{2}(?:[A-X]{2})?$/', $locator) !== 1) { set_flash('error', (string) $t['err_locator']); redirect('admin_members'); }
    db()->prepare('UPDATE members SET callsign = ?, full_name = ?, email = ?, locator = ?, is_active = ?, is_committee = ? WHERE id = ? LIMIT 1')->execute([$callsign, $fullName, $email, $locator, isset($_POST['is_active']) ? 1 : 0, isset($_POST['is_committee']) ? 1 : 0, (int) ($_POST['member_id'] ?? 0)]);
    set_flash('success', (string) $t['member_updated']);
    $postReturnQuery = trim((string) ($_POST['return_query'] ?? ''));
    redirect('admin_members' . ($postReturnQuery !== '' ? '&' . $postReturnQuery : ''));
}

$memberSearch = trim((string) ($_GET['member_q'] ?? ''));
$memberSort = (string) ($_GET['sort'] ?? 'callsign');
$memberPage = max(1, (int) ($_GET['page'] ?? 1));
$memberPerPage = 25;
$memberDir = strtolower((string) ($_GET['dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';
$allowedSort = ['callsign', 'full_name', 'email', 'locator', 'is_active', 'is_committee'];
if (!in_array($memberSort, $allowedSort, true)) { $memberSort = 'callsign'; }
$members = db()->query('SELECT id, callsign, full_name, email, locator, is_active, is_committee FROM members ORDER BY callsign')->fetchAll();
usort($members, static function (array $a, array $b) use ($memberSort, $memberDir): int { $cmp = strnatcasecmp((string) ($a[$memberSort] ?? ''), (string) ($b[$memberSort] ?? '')); return $memberDir === 'desc' ? -$cmp : $cmp; });
if ($memberSearch !== '') {
    $needle = mb_safe_strtolower($memberSearch);
    $members = array_values(array_filter($members, static function (array $m) use ($needle): bool {
        $hay = mb_safe_strtolower((string) ($m['callsign'] ?? '') . ' ' . (string) ($m['full_name'] ?? '') . ' ' . (string) ($m['email'] ?? ''));
        return str_contains($hay, $needle);
    }));
}
$memberTotal = count($members);
$memberPages = max(1, (int) ceil($memberTotal / $memberPerPage));
if ($memberPage > $memberPages) { $memberPage = $memberPages; }
$members = array_slice($members, ($memberPage - 1) * $memberPerPage, $memberPerPage);

ob_start();
?>
<section class="card">
    <h1><?= e((string) $t['title']) ?></h1>
    <form method="get" style="margin:.5rem 0 1rem;">
        <label><?= e((string) $t['search']) ?>
            <input type="text" name="member_q" value="<?= e($memberSearch) ?>" placeholder="<?= e((string) $t['search_ph']) ?>">
        </label>
        <input type="hidden" name="sort" value="<?= e($memberSort) ?>"><input type="hidden" name="dir" value="<?= e($memberDir) ?>"><button class="button secondary" type="submit">OK</button>
    </form>
    <div class="table-wrap"><table><thead><tr>
        <th><?= e((string) $t['th_callsign']) ?></th><th><?= e((string) $t['th_name']) ?></th><th><?= e((string) $t['th_email']) ?></th><th><?= e((string) $t['th_locator']) ?></th><th><?= e((string) $t['th_active']) ?></th><th><?= e((string) $t['th_committee']) ?></th><th><?= e((string) $t['th_actions']) ?></th>
    </tr></thead><tbody>
    <?php foreach ($members as $member): ?>
        <tr><td colspan="7"><form method="post" class="grid" style="grid-template-columns: 1fr 1fr 1fr 1fr auto auto auto; gap:.5rem; align-items:center;">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="member_id" value="<?= (int) $member['id'] ?>"><input type="hidden" name="return_query" value="<?= e($returnQuery) ?>">
            <input type="text" name="callsign" value="<?= e((string) $member['callsign']) ?>"><input type="text" name="full_name" value="<?= e((string) $member['full_name']) ?>"><input type="email" name="email" value="<?= e((string) $member['email']) ?>"><input type="text" name="locator" value="<?= e((string) $member['locator']) ?>" maxlength="6">
            <label><input type="checkbox" name="is_active" value="1" <?= (int) $member['is_active'] === 1 ? 'checked' : '' ?>></label>
            <label><input type="checkbox" name="is_committee" value="1" <?= (int) $member['is_committee'] === 1 ? 'checked' : '' ?>></label>
            <button class="button" type="submit"><?= e((string) $t['save']) ?></button>
        </form></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
</section>
<?php
echo render_layout((string) ob_get_clean(), (string) $t['layout']);
