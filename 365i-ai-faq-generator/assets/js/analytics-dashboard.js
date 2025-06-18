/**
 * Analytics Dashboard JavaScript
 *
 * Handles interaction and dynamic functionality for the analytics dashboard.
 *
 * @package AI_FAQ_Generator
 * @since 2.0.2
 */

jQuery(document).ready(function($) {
    'use strict';

    /**
     * Initialize the analytics dashboard
     */
    const AnalyticsDashboard = {
        /**
         * Initialize the dashboard
         */
        init: function() {
            this.initEventListeners();
            this.initDataRefresh();
        },

        /**
         * Initialize event listeners
         */
        initEventListeners: function() {
            // Period selector change
            $('#period-selector').on('change', function() {
                $(this).closest('form').submit();
            });

            // Run tests button
            $('.js-run-tests').on('click', function(e) {
                e.preventDefault();
                AnalyticsDashboard.runTests();
            });

            // View logs button
            $('.js-view-logs').on('click', function(e) {
                e.preventDefault();
                AnalyticsDashboard.viewLogs();
            });
        },

        /**
         * Initialize data refresh for real-time updates
         */
        initDataRefresh: function() {
            // If auto-refresh is enabled
            if ($('#auto-refresh').is(':checked')) {
                this.refreshInterval = setInterval(function() {
                    AnalyticsDashboard.refreshData();
                }, 60000); // Refresh every minute
            }

            // Toggle auto-refresh
            $('#auto-refresh').on('change', function() {
                if ($(this).is(':checked')) {
                    AnalyticsDashboard.refreshInterval = setInterval(function() {
                        AnalyticsDashboard.refreshData();
                    }, 60000);
                } else {
                    clearInterval(AnalyticsDashboard.refreshInterval);
                }
            });
        },

        /**
         * Refresh dashboard data via AJAX
         */
        refreshData: function() {
            const period = $('#period-selector').val();
            
            $.ajax({
                url: aiFaqGen.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_faq_get_analytics',
                    nonce: aiFaqGen.nonce,
                    period: period
                },
                beforeSend: function() {
                    $('.ai-faq-loading-overlay').fadeIn(200);
                },
                success: function(response) {
                    if (response.success) {
                        AnalyticsDashboard.updateDashboard(response.data);
                    } else {
                        console.error('Failed to refresh data:', response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                },
                complete: function() {
                    $('.ai-faq-loading-overlay').fadeOut(200);
                }
            });
        },

        /**
         * Update dashboard with new data
         * 
         * @param {Object} data The analytics data
         */
        updateDashboard: function(data) {
            // Update metrics
            $('#total-requests').text(data.total_requests.toLocaleString());
            $('#success-rate').text(data.success_rate + '%');
            $('#unique-users').text(data.unique_users.toLocaleString());
            $('#daily-average').text(data.daily_average.toLocaleString());
            $('#violations-count').text(data.violations.total_24h.toLocaleString());
            
            // Update charts
            if (window.dailyRequestsChart) {
                window.dailyRequestsChart.data.labels = data.daily_data.labels;
                window.dailyRequestsChart.data.datasets[0].data = data.daily_data.total;
                window.dailyRequestsChart.data.datasets[1].data = data.daily_data.success;
                window.dailyRequestsChart.data.datasets[2].data = data.daily_data.failed;
                window.dailyRequestsChart.update();
            }
            
            if (window.workerUsageChart) {
                window.workerUsageChart.data.labels = data.worker_performance.labels;
                window.workerUsageChart.data.datasets[0].data = data.worker_performance.requests;
                window.workerUsageChart.update();
            }
            
            if (window.successRatesChart) {
                window.successRatesChart.data.labels = data.worker_performance.labels;
                window.successRatesChart.data.datasets[0].data = data.worker_performance.success_rates;
                window.successRatesChart.update();
            }
            
            if (window.responseTimeChart) {
                window.responseTimeChart.data.labels = data.worker_performance.labels;
                window.responseTimeChart.data.datasets[0].data = data.worker_performance.response_times;
                window.responseTimeChart.update();
            }
            
            // Update worker performance table
            this.updateWorkerTable(data.worker_performance);
            
            // Update recent activity
            if (data.recent_activity) {
                this.updateActivityList(data.recent_activity);
            }
            
            // Update recent violations
            if (data.violations.recent) {
                this.updateViolationsTable(data.violations.recent);
            }
        },
        
        /**
         * Update the worker performance table
         * 
         * @param {Object} workerData Worker performance data
         */
        updateWorkerTable: function(workerData) {
            const $tableBody = $('.ai-faq-worker-performance-table tbody');
            
            if (!workerData.workers || !$tableBody.length) {
                return;
            }
            
            $tableBody.empty();
            
            workerData.workers.forEach(function(worker) {
                const statusClass = worker.success_rate >= 95 ? 'healthy' : 
                                   (worker.success_rate >= 80 ? 'warning' : 'error');
                const statusText = worker.success_rate >= 95 ? 'Healthy' : 
                                  (worker.success_rate >= 80 ? 'Warning' : 'Critical');
                                  
                $tableBody.append(`
                    <tr>
                        <td>${worker.name}</td>
                        <td>${worker.requests.toLocaleString()}</td>
                        <td>${worker.success_rate}%</td>
                        <td>${worker.avg_response_time} ms</td>
                        <td><span class="ai-faq-status-badge ${statusClass}">${statusText}</span></td>
                    </tr>
                `);
            });
        },
        
        /**
         * Update the recent activity list
         * 
         * @param {Array} activities Recent activity data
         */
        updateActivityList: function(activities) {
            const $activityList = $('.ai-faq-activity-list');
            
            if (!activities.length || !$activityList.length) {
                return;
            }
            
            $activityList.empty();
            
            activities.forEach(function(activity) {
                let icon = 'dashicons-info';
                let title = activity.activity_type;
                
                switch (activity.activity_type) {
                    case 'faq_generation':
                        icon = 'dashicons-editor-help';
                        title = `FAQ Generation (${activity.details.question_count || 0} questions)`;
                        break;
                    case 'settings_change':
                        icon = 'dashicons-admin-settings';
                        title = 'Settings Changed';
                        break;
                    case 'worker_test':
                        icon = 'dashicons-performance';
                        title = `Worker Test: ${activity.details.worker || ''}`;
                        break;
                }
                
                $activityList.append(`
                    <div class="ai-faq-activity-item">
                        <div class="ai-faq-activity-icon">
                            <span class="dashicons ${icon}"></span>
                        </div>
                        <div class="ai-faq-activity-content">
                            <div class="ai-faq-activity-title">${title}</div>
                            <div class="ai-faq-activity-meta">${activity.time_ago} ago</div>
                        </div>
                    </div>
                `);
            });
        },
        
        /**
         * Update the violations table
         * 
         * @param {Array} violations Recent violations data
         */
        updateViolationsTable: function(violations) {
            const $tableBody = $('.ai-faq-violations-table tbody');
            
            if (!violations.length || !$tableBody.length) {
                return;
            }
            
            $tableBody.empty();
            
            violations.forEach(function(violation) {
                $tableBody.append(`
                    <tr>
                        <td>${violation.ip}</td>
                        <td>${violation.worker}</td>
                        <td>${violation.requests_count}</td>
                        <td>${violation.limit}</td>
                        <td>${violation.time_ago} ago</td>
                    </tr>
                `);
            });
        },

        /**
         * Run tests from the dashboard
         */
        runTests: function() {
            if (!confirm('This will run tests against your Cloudflare Workers to verify functionality. Continue?')) {
                return;
            }
            
            $.ajax({
                url: aiFaqGen.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_faq_run_tests',
                    nonce: aiFaqGen.nonce
                },
                beforeSend: function() {
                    $('.js-run-tests').prop('disabled', true).text('Running Tests...');
                    $('.ai-faq-loading-overlay').fadeIn(200);
                },
                success: function(response) {
                    if (response.success) {
                        alert('Tests completed successfully. Refreshing dashboard...');
                        window.location.reload();
                    } else {
                        alert('Test execution failed: ' + response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    alert('An error occurred while running tests. Please check the console for details.');
                },
                complete: function() {
                    $('.js-run-tests').prop('disabled', false).text('Run Tests Again');
                    $('.ai-faq-loading-overlay').fadeOut(200);
                }
            });
        },

        /**
         * View test logs
         */
        viewLogs: function() {
            window.location.href = aiFaqGen.adminUrl + '?page=ai-faq-generator&action=view-test-logs';
        }
    };

    // Initialize the dashboard
    AnalyticsDashboard.init();
});