<#
.SYNOPSIS
    Test data generator for 365i AI FAQ Generator analytics
.DESCRIPTION
    Generates realistic usage data for the 365i AI FAQ Generator WordPress plugin
    to populate analytics dashboards and activity logs for development/testing.
.PARAMETER DaysOfData
    Number of days of historical data to generate
.PARAMETER EventsPerDay
    Average number of events to generate per day
.PARAMETER WpUrl
    WordPress site URL
.PARAMETER Username
    WordPress admin username
.PARAMETER Password
    WordPress admin password
.EXAMPLE
    .\generate-test-data.ps1 -DaysOfData 30 -EventsPerDay 25 -WpUrl "http://localhost/365i" -Username "superadmin" -Password "csHD@2005"
#>

param (
    [int]$DaysOfData = 30,
    [int]$EventsPerDay = 25,
    [string]$WpUrl = "http://localhost/365i",
    [string]$Username = "superadmin",
    [System.Security.SecureString]$PasswordAsSecureString = (ConvertTo-SecureString -String "csHD@2005" -AsPlainText -Force)
)

# WordPress REST API endpoints
$wpAuthEndpoint = "$WpUrl/wp-json/jwt-auth/v1/token"

# We keep these endpoints commented out for now as they're not used yet
# They will be used when API submission is enabled in the future
# $statsEndpoint = "$WpUrl/wp-json/ai-faq-gen/v1/stats"
# $activityEndpoint = "$WpUrl/wp-json/ai-faq-gen/v1/activity"

# FAQ topics for random generation
$faqTopics = @(
    "WordPress Development",
    "Plugin Installation",
    "Website Performance",
    "SEO Best Practices",
    "E-commerce Solutions",
    "Content Management",
    "User Authentication",
    "Database Optimization",
    "Security Measures",
    "API Integration",
    "Mobile Responsiveness",
    "Theme Customization"
)

# Worker types for simulation
$workerTypes = @(
    "question_generator",
    "answer_generator", 
    "faq_enhancer",
    "seo_analyzer",
    "faq_extractor",
    "topic_generator"
)

# User IPs for simulation (fictional)
$userIps = @(
    "192.168.1.100",
    "192.168.1.101",
    "192.168.1.102",
    "10.0.0.15",
    "10.0.0.16",
    "172.16.0.5",
    "172.16.0.6"
)

# Event types
$eventTypes = @(
    @{ Type = "faq_generation"; Weight = 70 },
    @{ Type = "worker_test"; Weight = 10 },
    @{ Type = "settings_change"; Weight = 5 },
    @{ Type = "rate_limit_violation"; Weight = 10 },
    @{ Type = "error"; Weight = 5 }
)

# Get authentication token
function Get-WpAuthToken {
    [CmdletBinding()]
    [OutputType([string])]
    param()
    
    Write-Verbose "Attempting to authenticate with WordPress at $wpAuthEndpoint"
    
    # Convert secure string password back to plain text for API
    $credential = New-Object System.Management.Automation.PSCredential($Username, $PasswordAsSecureString)
    $plainPassword = $credential.GetNetworkCredential().Password
    
    $authBody = @{
        username = $Username
        password = $plainPassword
    } | ConvertTo-Json
    
    try {
        $params = @{
            Uri         = $wpAuthEndpoint
            Method      = 'Post'
            Body        = $authBody
            ContentType = 'application/json'
            ErrorAction = 'Stop'
        }
        
        $authResponse = Invoke-RestMethod @params
        
        if (-not $authResponse.token) {
            throw "Authentication response did not contain a token"
        }
        
        Write-Verbose "Successfully authenticated as $Username"
        return $authResponse.token
    }
    catch {
        $errorDetails = "Authentication failed: $($_.Exception.Message)"
        if ($_.ErrorDetails.Message) {
            $errorDetails += "`nDetails: $($_.ErrorDetails.Message)"
        }
        
        Write-Error $errorDetails
        throw "Failed to authenticate with WordPress: $($_.Exception.Message)"
    }
}

# Generate a random event based on weights
function Get-RandomEvent {
    [CmdletBinding()]
    [OutputType([string])]
    param()
    
    try {
        $totalWeight = ($eventTypes | Measure-Object -Property Weight -Sum).Sum
        $randomNumber = Get-Random -Minimum 1 -Maximum ($totalWeight + 1)
        
        $currentWeight = 0
        foreach ($event in $eventTypes) {
            $currentWeight += $event.Weight
            if ($randomNumber -le $currentWeight) {
                return $event.Type
            }
        }
        
        # Fallback in case of unexpected behavior
        Write-Verbose "Fallback to default event type"
        return $eventTypes[0].Type
    }
    catch {
        Write-Error "Error selecting random event: $_"
        return "faq_generation"  # Failsafe default
    }
}

# Generate a random date within the specified range
function Get-RandomDate {
    [CmdletBinding()]
    [OutputType([datetime])]
    param (
        [Parameter(Mandatory = $true)]
        [ValidateNotNull()]
        [datetime]$StartDate,
        
        [Parameter(Mandatory = $true)]
        [ValidateNotNull()]
        [datetime]$EndDate
    )
    
    try {
        if ($EndDate -le $StartDate) {
            throw "EndDate must be greater than StartDate"
        }
        
        $timeSpan = New-TimeSpan -Start $StartDate -End $EndDate
        $randomSeconds = Get-Random -Minimum 0 -Maximum ([Math]::Floor($timeSpan.TotalSeconds))
        return $StartDate.AddSeconds($randomSeconds)
    }
    catch {
        Write-Error "Error generating random date: $_"
        return $StartDate  # Return start date as fallback
    }
}

# Generate a random response time between 0.5 and 8 seconds with distribution favoring lower times
function Get-RandomResponseTime {
    [CmdletBinding()]
    [OutputType([double])]
    param()
    
    try {
        $baseTime = Get-Random -Minimum 500 -Maximum 2000
        $randomValue = Get-Random -Minimum 0 -Maximum 100
        $additionalTime = [Math]::Pow($randomValue, 2) / 100 * 6000
        return [Math]::Round(($baseTime + $additionalTime) / 1000, 2)  # Round to 2 decimal places
    }
    catch {
        Write-Error "Error generating random response time: $_"
        return 1.0  # Return a reasonable default
    }
}

# Generate a simulated FAQ generation event
function New-FaqGenerationEvent {
    param (
        [datetime]$Timestamp
    )
    
    $topic = $faqTopics | Get-Random
    $questionCount = Get-Random -Minimum 3 -Maximum 15
    $userIp = $userIps | Get-Random
    $success = (Get-Random -Minimum 0 -Maximum 100) -lt 95  # 95% success rate
    $responseTime = Get-RandomResponseTime
    
    # Workers used
    $workersUsed = @{}
    foreach ($worker in ($workerTypes | Get-Random -Count (Get-Random -Minimum 2 -Maximum 5))) {
        $workersUsed[$worker] = @{
            requests = Get-Random -Minimum 1 -Maximum ($questionCount + 2)
            success = if ($success) { Get-Random -Minimum 1 -Maximum ($questionCount + 2) } else { 0 }
            response_time = Get-RandomResponseTime
        }
    }
    
    return @{
        event_type = "faq_generation"
        timestamp = $Timestamp.ToString("yyyy-MM-ddTHH:mm:ss")
        user_id = 1
        user_ip = $userIp
        topic = $topic
        question_count = $questionCount
        success = $success
        workers_used = $workersUsed
        response_time = $responseTime
        details = @{
            url = ""
            source = Get-Random -InputObject @("manual", "url", "content")
        }
    }
}

# Generate a simulated worker test event
function New-WorkerTestEvent {
    param (
        [datetime]$Timestamp
    )
    
    $worker = $workerTypes | Get-Random
    $success = (Get-Random -Minimum 0 -Maximum 100) -lt 90  # 90% success rate
    $responseTime = Get-RandomResponseTime
    
    return @{
        event_type = "worker_test"
        timestamp = $Timestamp.ToString("yyyy-MM-ddTHH:mm:ss")
        user_id = 1
        worker = $worker
        success = $success
        response_time = $responseTime
        details = @{
            status_code = if ($success) { 200 } else { Get-Random -InputObject @(403, 404, 500, 502, 504) }
        }
    }
}

# Generate a simulated settings change event
function New-SettingsChangeEvent {
    param (
        [datetime]$Timestamp
    )
    
    $settingsChanged = @(
        "cloudflare_account_id",
        "cloudflare_api_token",
        "default_tone",
        "default_length",
        "enable_rate_limiting",
        "worker_urls",
        "cache_duration"
    ) | Get-Random -Count (Get-Random -Minimum 1 -Maximum 4)
    
    return @{
        event_type = "settings_change"
        timestamp = $Timestamp.ToString("yyyy-MM-ddTHH:mm:ss")
        user_id = 1
        settings_changed = $settingsChanged
        details = @{
            previous_values = @{}
            new_values = @{}
        }
    }
}

# Generate a simulated rate limit violation event
function New-RateLimitViolationEvent {
    param (
        [datetime]$Timestamp
    )
    
    $userIp = $userIps | Get-Random
    $worker = $workerTypes | Get-Random
    
    return @{
        event_type = "rate_limit_violation"
        timestamp = $Timestamp.ToString("yyyy-MM-ddTHH:mm:ss")
        user_ip = $userIp
        worker = $worker
        requests_count = Get-Random -Minimum 50 -Maximum 200
        limit = Get-Random -InputObject @(50, 100, 150, 200)
        details = @{
            blocked = (Get-Random -Minimum 0 -Maximum 100) -lt 30  # 30% chance of being blocked
        }
    }
}

# Generate a simulated error event
function New-ErrorEvent {
    param (
        [datetime]$Timestamp
    )
    
    $errorTypes = @(
        "api_connection_failed",
        "worker_timeout",
        "invalid_response",
        "authentication_failed",
        "unexpected_error"
    )
    
    $worker = $workerTypes | Get-Random
    $errorType = $errorTypes | Get-Random
    
    return @{
        event_type = "error"
        timestamp = $Timestamp.ToString("yyyy-MM-ddTHH:mm:ss")
        worker = $worker
        error_type = $errorType
        details = @{
            message = "Simulated error: $errorType for $worker"
            code = Get-Random -Minimum 400 -Maximum 599
        }
    }
}

# Main data generation function
function Start-TestDataGeneration {
    [CmdletBinding()]
    param()
    
    Write-Host "Generating test data for $DaysOfData days with ~$EventsPerDay events per day..."
    
    # Calculate date range
    $endDate = Get-Date
    $startDate = $endDate.AddDays(-$DaysOfData)
    
    # Authentication
    Write-Host "Authenticating with WordPress..."
    try {
        $token = Get-WpAuthToken
        
        if (-not $token) {
            Write-Error "Failed to obtain authentication token."
            exit 1
        }
    }
    catch {
        Write-Error "Authentication error: $_"
        exit 1
    }
    
    # Create output directory if it doesn't exist
    $outputDir = "test-data"
    if (-not (Test-Path -Path $outputDir)) {
        try {
            New-Item -Path $outputDir -ItemType Directory -Force | Out-Null
            Write-Host "Created output directory: $outputDir"
        }
        catch {
            Write-Error "Failed to create output directory: $_"
            exit 1
        }
    }
    
    # Generate events for each day
    for ($day = 0; $day -lt $DaysOfData; $day++) {
        $currentDate = $startDate.AddDays($day)
        $nextDate = $startDate.AddDays($day + 1)
        
        # Vary the number of events per day slightly
        $varianceFactor = Get-Random -Minimum 0.7 -Maximum 1.3
        $eventsForDay = [Math]::Max(1, [Math]::Round($EventsPerDay * $varianceFactor))
        
        Write-Host "Generating $eventsForDay events for $($currentDate.ToString('yyyy-MM-dd'))..."
        
        # Generate events for this day
        for ($i = 0; $i -lt $eventsForDay; $i++) {
            # Generate random timestamp for this day
            $timestamp = Get-RandomDate -StartDate $currentDate -EndDate $nextDate
            
            # Determine event type
            $eventType = Get-RandomEvent
            
            # Generate the event based on type
            $eventData = switch ($eventType) {
                "faq_generation" { New-FaqGenerationEvent -Timestamp $timestamp }
                "worker_test" { New-WorkerTestEvent -Timestamp $timestamp }
                "settings_change" { New-SettingsChangeEvent -Timestamp $timestamp }
                "rate_limit_violation" { New-RateLimitViolationEvent -Timestamp $timestamp }
                "error" { New-ErrorEvent -Timestamp $timestamp }
                default { New-FaqGenerationEvent -Timestamp $timestamp }
            }
            
            # Convert to JSON
            $eventJson = $eventData | ConvertTo-Json -Depth 5
            
            # We determine which endpoint to use for real API submission, though we're not using it now
            # This is kept for when the API submission is enabled
            <#
            $apiEndpoint = if ($eventData.event_type -eq "faq_generation" -or $eventData.event_type -eq "worker_test") {
                $statsEndpoint
            } else {
                $activityEndpoint
            }
            #>
            
            # Send to API (commented out to prevent actual submission in this example)
            <#
            # Determine which endpoint to use
            $apiEndpoint = if ($eventData.event_type -eq "faq_generation" -or $eventData.event_type -eq "worker_test") {
                "$WpUrl/wp-json/ai-faq-gen/v1/stats"
            } else {
                "$WpUrl/wp-json/ai-faq-gen/v1/activity"
            }
            
            try {
                $response = Invoke-RestMethod -Uri $apiEndpoint -Method Post -Body $eventJson -ContentType "application/json" -Headers @{
                    "Authorization" = "Bearer $token"
                }
                Write-Host "  Event submitted successfully: $($eventData.event_type)"
            }
            catch {
                Write-Warning "  Failed to submit event: $_"
            }
            #>
            
            # For demo purposes, just write to file instead of API submission
            $fileName = "event_$($timestamp.ToString('yyyyMMdd_HHmmss'))_$($eventData.event_type).json"
            $filePath = Join-Path -Path $outputDir -ChildPath $fileName
            
            try {
                $eventJson | Out-File -FilePath $filePath -Encoding utf8
                Write-Host "  Generated event: $filePath"
            }
            catch {
                Write-Warning "  Failed to write event to file: $_"
            }
            
            # Add a small delay to prevent overwhelming the system
            Start-Sleep -Milliseconds 50
        }
    }
    
    Write-Host "Test data generation complete. Generated files in the $outputDir directory."
    Write-Host "To import this data, use the import-test-data.php script in the tools directory."
}

# Execute the main function
Start-TestDataGeneration