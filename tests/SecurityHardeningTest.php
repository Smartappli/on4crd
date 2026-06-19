<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SecurityHardeningTest extends TestCase
{
    public function testRootHtaccessBlocksDirectAccessToInternalApplicationDirectories(): void
    {
        $contents = file_get_contents(__DIR__ . '/../.htaccess');
        self::assertIsString($contents);

        self::assertStringContainsString('storage/private', $contents);
        self::assertStringContainsString('storage/uploads/(?:library|member_modules)', $contents);
    }

    public function testStorageHtaccessForcesActiveContentToDownload(): void
    {
        foreach (['storage/.htaccess', 'storage/uploads/.htaccess', 'storage/press/.htaccess'] as $relativePath) {
            $contents = file_get_contents(__DIR__ . '/../' . $relativePath);
            self::assertIsString($contents);

            self::assertStringContainsString('FilesMatch "\\.(?:php|phtml|phar|cgi|pl|py|sh|exe)$"', $contents, $relativePath);
            self::assertStringContainsString('Require all denied', $contents, $relativePath);
            self::assertStringContainsString('FilesMatch "\\.(?:html?|svg|xml)$"', $contents, $relativePath);
            self::assertStringContainsString('ForceType application/octet-stream', $contents, $relativePath);
            self::assertStringContainsString('Header set Content-Disposition "attachment"', $contents, $relativePath);
            self::assertStringContainsString('Header set X-Content-Type-Options "nosniff"', $contents, $relativePath);
        }
    }

    public function testMemberDocumentUploadDirectoriesAreBlockedFromDirectWebAccess(): void
    {
        $uploads = file_get_contents(__DIR__ . '/../storage/uploads/.htaccess');
        $private = file_get_contents(__DIR__ . '/../storage/private/.htaccess');
        self::assertIsString($uploads);
        self::assertIsString($private);

        self::assertStringContainsString('RewriteRule ^(?:library|member_modules)(?:/|$) - [F,L,NC]', $uploads);
        self::assertStringContainsString('Require all denied', $private);
    }

    public function testPublicRegisterIsClosedAndDoesNotGrantAdminRoles(): void
    {
        $register = file_get_contents(__DIR__ . '/../pages/register.php');
        $login = file_get_contents(__DIR__ . '/../pages/login.php');
        self::assertIsString($register);
        self::assertIsString($login);

        self::assertStringContainsString('Public registration is closed', $register);
        self::assertStringNotContainsString('createUserWithUniqueUsername', $register);
        self::assertStringNotContainsString('ON DUPLICATE KEY UPDATE', $register);
        self::assertStringNotContainsString('ensure_configured_administrator_roles', $register);
        self::assertStringNotContainsString("route_url('register')", $login);
        self::assertStringContainsString("route_url('membership')", $login);
    }

    public function testMemberDocumentsAreStoredPrivatelyAndServedThroughControllers(): void
    {
        $libraryHelpers = file_get_contents(__DIR__ . '/../app/member_library_helpers.php');
        $moduleHelpers = file_get_contents(__DIR__ . '/../app/member_module_documents.php');
        $libraryPreview = file_get_contents(__DIR__ . '/../pages/member_library_preview.php');
        $modulePreview = file_get_contents(__DIR__ . '/../pages/member_document_preview.php');
        self::assertIsString($libraryHelpers);
        self::assertIsString($moduleHelpers);
        self::assertIsString($libraryPreview);
        self::assertIsString($modulePreview);

        self::assertStringContainsString('/storage/private/library', $libraryHelpers);
        self::assertStringContainsString('/storage/private/member_modules/', $moduleHelpers);
        self::assertStringContainsString("route_url('member_document_preview'", $moduleHelpers);
        self::assertStringNotContainsString('base_url($safePath)', $moduleHelpers);
        self::assertStringContainsString('require_login();', $libraryPreview);
        self::assertStringContainsString('require_login();', $modulePreview);
        self::assertStringContainsString('storage_document_absolute_path($safePath)', $libraryPreview);
        self::assertStringContainsString('storage_document_absolute_path($safePath)', $modulePreview);
    }

    public function testRichHtmlSanitizerUsesStrictAllowlist(): void
    {
        require_once __DIR__ . '/../app/functions.php';

        $clean = sanitize_rich_html('<p style="position:fixed" id="x">OK</p><svg><a xlink:href="javascript:alert(1)">bad</a></svg><form><input name="token"><button>Send</button></form>');

        self::assertStringContainsString('<p>OK</p>', $clean);
        self::assertStringContainsString('Send', $clean);
        self::assertStringNotContainsString('<svg', $clean);
        self::assertStringNotContainsString('xlink:href', $clean);
        self::assertStringNotContainsString('javascript:', $clean);
        self::assertStringNotContainsString('style=', $clean);
        self::assertStringNotContainsString('<form', $clean);
        self::assertStringNotContainsString('<input', $clean);
    }

    public function testForgotPasswordPublicResponseIsUniformExceptRateLimit(): void
    {
        $forgotPassword = file_get_contents(__DIR__ . '/../pages/forgot_password.php');
        self::assertIsString($forgotPassword);

        self::assertStringContainsString("set_flash('success', \$t('ok_sent'));", $forgotPassword);
        self::assertStringContainsString("catch (\\Delight\\Auth\\TooManyRequestsException", $forgotPassword);
        self::assertStringNotContainsString("set_flash('error', \$t('err_invalid_email'))", $forgotPassword);
        self::assertStringNotContainsString("set_flash('error', \$t('err_not_verified'))", $forgotPassword);
        self::assertStringNotContainsString("set_flash('error', \$t('err_reset_disabled'))", $forgotPassword);
    }

    public function testPublicContactFormsHaveCaptchaHoneypotAndRateLimit(): void
    {
        $requestSecurity = file_get_contents(__DIR__ . '/../app/request_security.php');
        $footerContact = file_get_contents(__DIR__ . '/../pages/footer_contact.php');
        $home = file_get_contents(__DIR__ . '/../pages/home.php');
        $donation = file_get_contents(__DIR__ . '/../pages/donation.php');
        $ideaSubmit = file_get_contents(__DIR__ . '/../pages/idea_submit.php');
        $layoutRenderer = file_get_contents(__DIR__ . '/../app/layout_renderer.php');
        self::assertIsString($requestSecurity);
        self::assertIsString($footerContact);
        self::assertIsString($home);
        self::assertIsString($donation);
        self::assertIsString($ideaSubmit);
        self::assertIsString($layoutRenderer);

        self::assertStringContainsString('function public_form_rate_limit', $requestSecurity);
        self::assertStringContainsString('function public_form_verify_captcha', $requestSecurity);
        self::assertStringContainsString("public_form_rate_limit('footer_contact'", $footerContact);
        self::assertStringContainsString("public_form_verify_captcha('footer_contact'", $footerContact);
        self::assertStringContainsString('name="contact_captcha"', $home);
        self::assertStringContainsString('name="contact_captcha"', $donation);
        self::assertStringContainsString('name="contact_website"', $home);
        self::assertStringContainsString('name="contact_website"', $donation);
        self::assertStringContainsString("public_form_rate_limit('idea_submit'", $ideaSubmit);
        self::assertStringContainsString('name="idea_website"', $layoutRenderer);
    }

    public function testPostHandlersKeepCsrfVerification(): void
    {
        $roots = [__DIR__ . '/../pages', __DIR__ . '/../app'];

        foreach ($roots as $root) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $fileInfo) {
                if (!$fileInfo instanceof SplFileInfo || $fileInfo->getExtension() !== 'php') {
                    continue;
                }

                $path = $fileInfo->getPathname();
                $contents = file_get_contents($path);
                self::assertIsString($contents);

                $handlesPost = str_contains($contents, '$_POST')
                    || preg_match('/REQUEST_METHOD.{0,160}POST|POST.{0,160}REQUEST_METHOD/s', $contents) === 1;
                if (!$handlesPost) {
                    continue;
                }

                self::assertStringContainsString('verify_csrf(', $contents, sprintf('POST handler without CSRF verification: %s', $path));
            }
        }
    }
}
