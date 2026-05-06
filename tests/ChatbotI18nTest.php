<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ChatbotI18nTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
        $_SERVER = [];
        $_POST = [];
        $_GET = [];
    }

    public function testEmptyQuestionFallbackIsLocalizedInEnglish(): void
    {
        $_SESSION['locale'] = 'en';

        $result = answer_question_from_knowledge('   ');

        self::assertSame('I did not receive a usable question.', $result['answer']);
        self::assertSame('Raymond assistant', $result['source']);
    }

    public function testEmptyQuestionFallbackIsLocalizedInGerman(): void
    {
        $_SESSION['locale'] = 'de';

        $result = answer_question_from_knowledge('');

        self::assertSame('Ich habe keine verwertbare Frage erhalten.', $result['answer']);
        self::assertSame('Assistent Raymond', $result['source']);
    }

    public function testEmptyQuestionFallbackIsLocalizedInFrench(): void
    {
        $_SESSION['locale'] = 'fr';

        $result = answer_question_from_knowledge(' ');

        self::assertSame('Je n’ai pas reçu de question exploitable.', $result['answer']);
        self::assertSame('Assistant Raymond', $result['source']);
    }

    public function testEmptyQuestionFallbackIsLocalizedInDutch(): void
    {
        $_SESSION['locale'] = 'nl';

        $result = answer_question_from_knowledge("\n");

        self::assertSame('Ik heb geen bruikbare vraag ontvangen.', $result['answer']);
        self::assertSame('Raymond-assistent', $result['source']);
    }

    public function testEmptyQuestionFallbackDefaultsToFrenchForUnknownLocale(): void
    {
        $_SESSION['locale'] = 'it';

        $result = answer_question_from_knowledge('');

        self::assertSame('Je n’ai pas reçu de question exploitable.', $result['answer']);
        self::assertSame('Assistant Raymond', $result['source']);
    }

    public function testEmptyQuestionFallbackDefaultsToFrenchWithoutLocale(): void
    {
        unset($_SESSION['locale']);

        $result = answer_question_from_knowledge('');

        self::assertSame('Je n’ai pas reçu de question exploitable.', $result['answer']);
        self::assertSame('Assistant Raymond', $result['source']);
    }
}
