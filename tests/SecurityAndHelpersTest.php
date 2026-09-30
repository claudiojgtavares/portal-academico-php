<?php

use PHPUnit\Framework\TestCase;

final class SecurityAndHelpersTest extends TestCase
{
    public function testEscapingProtectsMarkup(): void
    {
        $this->assertSame('&lt;script&gt;', e('<script>'));
    }

    public function testInstitutionalEmailUsesExampleDomain(): void
    {
        $this->assertSame('ana.lima.esi001@academico.example.test', generate_institutional_email('Ana Lima', 'ESI-001'));
    }

    public function testPublicTreeHasNoOldInstitutionOrWorldWritableUploads(): void
    {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__)));
        foreach ($files as $file) {
            if (!$file->isFile() || in_array($file->getExtension(), ['png', 'jpg', 'jpeg', 'gif'], true)) {
                continue;
            }
            $contents = file_get_contents($file->getPathname());
            $this->assertStringNotContainsStringIgnoringCase('unipiaget', $contents, $file->getPathname());
            $this->assertStringNotContainsString('0777', $contents, $file->getPathname());
        }
    }
}
