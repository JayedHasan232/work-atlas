<?php

test('the home page loads without JavaScript errors or accessibility issues', function () {
    visit('/')
        ->assertTitleContains(config('app.name'))
        ->assertNoJavaScriptErrors()
        ->assertNoAccessibilityIssues();
});
