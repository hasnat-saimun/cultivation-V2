<?php

namespace Tests\Feature;

use Tests\TestCase;

class StaticTextCaretStyleTest extends TestCase
{
    public function test_shared_admin_head_scopes_static_caret_without_disabling_selection_or_editors(): void
    {
        $styles = file_get_contents(resource_path('views/cultivation/includeSection.blade.php'));

        $this->assertStringContainsString('caret-color: transparent;', $styles);
        $this->assertStringContainsString('[contenteditable="true"]', $styles);
        $this->assertStringContainsString('.select2-search__field', $styles);
        $this->assertStringContainsString('.dataTables_filter input', $styles);
        $this->assertStringContainsString('caret-color: auto;', $styles);
        $this->assertStringNotContainsString('user-select: none', $styles);
    }
}
