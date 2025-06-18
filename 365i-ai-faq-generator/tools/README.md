# 365i AI FAQ Generator Testing Tools

This directory contains tools for testing and analyzing the 365i AI FAQ Generator plugin functionality, especially the Cloudflare Workers API.

## Overview

These tools help test the worker API endpoints directly, simulating frontend requests to verify functionality and generate usage data for analytics dashboards.

## Tools Included

### 1. `test-workers.js`

A Node.js script that tests all Cloudflare Worker endpoints with properly formatted requests. It simulates real user interactions from the frontend FAQ generator tool.

Features:
- Tests all worker endpoints individually or as an end-to-end workflow
- Generates realistic test load to simulate user activity
- Records response times and success rates
- Logs detailed request/response data for analysis
- Provides comprehensive testing summary

### 2. `generate-test-data.ps1`

A PowerShell script for generating synthetic analytics data for testing the admin dashboard's statistics and visualization features.

## Requirements

- Node.js 14+ for running JavaScript tests
- PowerShell 5.1+ for running PowerShell scripts
- npm for installing dependencies

## Installation

1. Navigate to the tools directory:
   ```
   cd plugins/365i-ai-faq-generator/tools
   ```

2. Install dependencies:
   ```
   npm install
   ```

## Usage

### Testing Worker API Endpoints

Run the default test configuration:
```
npm test
```

Run with fewer iterations (faster, less intensive):
```
npm run test:small
```

Run medium-sized test suite:
```
npm run test:medium
```

Run comprehensive test suite (more iterations, longer delay):
```
npm run test:large
```

### Custom Test Configuration

You can customize test parameters directly:

```
node test-workers.js --iterations=20 --delay=800
```

Available parameters:
- `iterations`: Number of test operations to perform
- `delay`: Milliseconds to wait between requests
- `save-logs`: Whether to save detailed logs (true/false)

### Working with Test Results

All test results are saved in the `logs` subdirectory. These include:
- JSON files with detailed request/response data for each test
- Summary statistics at the end of a test run

## Analyzing the Data

The test output can be used to:
1. Verify worker functionality
2. Identify performance bottlenecks
3. Simulate load for testing rate limiting
4. Generate sample data for the admin analytics dashboard
5. Debug issues with specific API endpoints

## Notes for Developers

- The test scripts use the actual worker endpoints configured in the script. 
- Be cautious when running large test suites as they may count against your Cloudflare Workers usage limits.
- For development/testing, consider using test-specific worker instances to avoid affecting production usage analytics.