@echo off
REM Script to run all tests for the 365i AI FAQ Generator plugin
REM This script runs both syntax checks and component tests

echo ========================================
echo Running 365i AI FAQ Generator Test Suite
echo ========================================
echo.

REM 1. Run syntax check
echo Step 1: Running PHP Syntax Check
echo --------------------------------
php syntax-check.php
set SYNTAX_RESULT=%ERRORLEVEL%

echo.

REM 2. Run component tests
echo Step 2: Running Component Tests
echo -------------------------------
php test-components.php
set COMPONENT_RESULT=%ERRORLEVEL%

echo.
echo ========================================
echo Test Results Summary
echo ========================================

REM Check results
if %SYNTAX_RESULT% EQU 0 (
    if %COMPONENT_RESULT% EQU 0 (
        echo ✅ All tests passed successfully!
        echo The plugin is ready for deployment.
        exit /b 0
    ) else (
        echo ❌ Some tests failed. Please review the output above.
        exit /b 1
    )
) else (
    echo ❌ Some tests failed. Please review the output above.
    exit /b 1
)