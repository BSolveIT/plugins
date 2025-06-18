/**
 * Worker Testing Script for 365i AI FAQ Generator
 * 
 * This script sends test requests directly to the Cloudflare Workers that power
 * the AI FAQ Generator. It simulates real frontend requests to test worker functionality
 * and generate usage data for the analytics dashboard.
 * 
 * Run with: node test-workers.js
 */

const fetch = require('node-fetch');
const fs = require('fs');
const path = require('path');

// Configuration - Edit these values
const CONFIG = {
    // Number of test iterations to run
    iterations: 100,
    
    // Delay between requests in milliseconds (to avoid rate limiting)
    delay: 1000,
    
    // Whether to save requests and responses to log files
    saveToLogs: true,
    
    // Worker endpoints
    workers: {
        questionGenerator: 'https://faq-answer-generator-worker.winter-cake-bf57.workers.dev/generate-questions',
        answerGenerator: 'https://faq-answer-generator-worker.winter-cake-bf57.workers.dev/generate-answers',
        faqEnhancer: 'https://faq-enhancement-worker.winter-cake-bf57.workers.dev/enhance',
        seoAnalyzer: 'https://faq-seo-analyzer-worker.winter-cake-bf57.workers.dev/analyze',
        faqExtractor: 'https://faq-proxy-fetch.winter-cake-bf57.workers.dev/extract',
        topicGenerator: 'https://url-to-faq-generator-worker.winter-cake-bf57.workers.dev/generate-topics'
    }
};

// Sample topics for testing
const SAMPLE_TOPICS = [
    'WordPress plugin development',
    'Website performance optimization',
    'SEO best practices',
    'E-commerce solutions',
    'Content management systems',
    'Web hosting options',
    'Frontend frameworks',
    'Mobile responsive design',
    'Website security',
    'User authentication methods',
    'API integration strategies',
    'Database optimization'
];

// Sample content for extraction and enhancement
const SAMPLE_CONTENT = [
    `WordPress is a free and open-source content management system written in PHP and paired with a MySQL or MariaDB database. Features include a plugin architecture and a template system, referred to within WordPress as Themes. WordPress was originally created as a blog-publishing system but has evolved to support other types of web content including more traditional mailing lists and forums, media galleries, membership sites, learning management systems (LMS) and online stores.`,
    
    `Search engine optimization (SEO) is the process of improving the quality and quantity of website traffic to a website or a web page from search engines. SEO targets unpaid traffic (known as "natural" or "organic" results) rather than direct traffic or paid traffic. Unpaid traffic may originate from different kinds of searches, including image search, video search, academic search, news search, and industry-specific vertical search engines.`,
    
    `Responsive web design (RWD) is an approach to web design that makes web pages render well on a variety of devices and window or screen sizes. Recent work also considers the viewer proximity as part of the viewing context as an extension for RWD. Content, design and performance are necessary across all devices to ensure usability and satisfaction.`
];

// Sample URLs for testing
const SAMPLE_URLS = [
    'https://wordpress.org/documentation/',
    'https://developers.google.com/search/docs/beginner/seo-starter-guide',
    'https://www.smashingmagazine.com/2011/01/guidelines-for-responsive-web-design/',
    'https://www.cloudflare.com/learning/performance/what-is-web-optimization/',
    'https://kinsta.com/blog/wordpress-security/'
];

// Utility function to wait for a specified time
const sleep = (ms) => new Promise(resolve => setTimeout(resolve, ms));

// Utility function to get a random item from an array
const getRandomItem = (array) => array[Math.floor(Math.random() * array.length)];

// Utility function to ensure log directory exists
const ensureLogDir = () => {
    const logDir = path.join(__dirname, 'logs');
    if (!fs.existsSync(logDir)) {
        fs.mkdirSync(logDir, { recursive: true });
    }
    return logDir;
};

// Utility function to save request/response to log file
const saveToLog = (workerName, data) => {
    if (!CONFIG.saveToLogs) return;
    
    const logDir = ensureLogDir();
    const timestamp = new Date().toISOString().replace(/:/g, '-').replace(/\..+/, '');
    const logFile = path.join(logDir, `${timestamp}_${workerName}.json`);
    
    fs.writeFileSync(logFile, JSON.stringify(data, null, 2));
    console.log(`Log saved to ${logFile}`);
};

// Test the Question Generator Worker
async function testQuestionGenerator() {
    console.log('\n🔍 Testing Question Generator Worker...');
    
    const topic = getRandomItem(SAMPLE_TOPICS);
    const count = Math.floor(Math.random() * 8) + 3; // 3-10 questions
    
    const requestBody = {
        topic: topic,
        count: count,
        tone: getRandomItem(['professional', 'casual', 'technical', 'friendly']),
        clientId: 'test-script'
    };
    
    console.log(`Requesting ${count} questions about "${topic}"`);
    
    try {
        const startTime = Date.now();
        const response = await fetch(CONFIG.workers.questionGenerator, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'User-Agent': '365i-FAQ-Generator-Test-Script/1.0'
            },
            body: JSON.stringify(requestBody)
        });
        const responseTime = Date.now() - startTime;
        
        const data = await response.json();
        
        const logData = {
            timestamp: new Date().toISOString(),
            request: requestBody,
            response: data,
            status: response.status,
            responseTime: responseTime
        };
        
        saveToLog('question-generator', logData);
        
        if (response.ok) {
            console.log(`✅ Success! Generated ${data.questions?.length || 0} questions in ${responseTime}ms`);
            if (data.questions && data.questions.length > 0) {
                console.log('Sample question: ' + data.questions[0]);
            }
        } else {
            console.log(`❌ Error: ${response.status} - ${data.error || 'Unknown error'}`);
        }
        
        return { success: response.ok, data, responseTime };
    } catch (error) {
        console.error(`❌ Failed to test Question Generator: ${error.message}`);
        return { success: false, error: error.message };
    }
}

// Test the Answer Generator Worker
async function testAnswerGenerator(questions = null) {
    console.log('\n📝 Testing Answer Generator Worker...');
    
    // If no questions provided, create some
    if (!questions) {
        questions = [];
        const count = Math.floor(Math.random() * 3) + 1; // 1-3 questions
        for (let i = 0; i < count; i++) {
            questions.push(`What is ${getRandomItem(SAMPLE_TOPICS)}?`);
        }
    }
    
    const requestBody = {
        questions: questions,
        tone: getRandomItem(['professional', 'casual', 'technical', 'friendly']),
        length: getRandomItem(['short', 'medium', 'long']),
        clientId: 'test-script'
    };
    
    console.log(`Requesting answers for ${questions.length} questions`);
    
    try {
        const startTime = Date.now();
        const response = await fetch(CONFIG.workers.answerGenerator, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'User-Agent': '365i-FAQ-Generator-Test-Script/1.0'
            },
            body: JSON.stringify(requestBody)
        });
        const responseTime = Date.now() - startTime;
        
        const data = await response.json();
        
        const logData = {
            timestamp: new Date().toISOString(),
            request: requestBody,
            response: data,
            status: response.status,
            responseTime: responseTime
        };
        
        saveToLog('answer-generator', logData);
        
        if (response.ok) {
            console.log(`✅ Success! Generated ${data.faqs?.length || 0} answers in ${responseTime}ms`);
            if (data.faqs && data.faqs.length > 0) {
                console.log(`Sample Q: ${data.faqs[0].question}`);
                console.log(`Sample A: ${data.faqs[0].answer.substring(0, 100)}...`);
            }
        } else {
            console.log(`❌ Error: ${response.status} - ${data.error || 'Unknown error'}`);
        }
        
        return { success: response.ok, data, responseTime };
    } catch (error) {
        console.error(`❌ Failed to test Answer Generator: ${error.message}`);
        return { success: false, error: error.message };
    }
}

// Test the FAQ Enhancer Worker
async function testFaqEnhancer() {
    console.log('\n✨ Testing FAQ Enhancer Worker...');
    
    // Create some sample FAQs
    const faqs = [];
    const count = Math.floor(Math.random() * 3) + 1; // 1-3 FAQs
    
    for (let i = 0; i < count; i++) {
        faqs.push({
            question: `What is ${getRandomItem(SAMPLE_TOPICS)}?`,
            answer: getRandomItem(SAMPLE_CONTENT).substring(0, 150) + '...'
        });
    }
    
    const requestBody = {
        faqs: faqs,
        enhancementType: getRandomItem(['improve_quality', 'add_details', 'simplify', 'technical_accuracy']),
        clientId: 'test-script'
    };
    
    console.log(`Requesting enhancement for ${faqs.length} FAQs`);
    
    try {
        const startTime = Date.now();
        const response = await fetch(CONFIG.workers.faqEnhancer, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'User-Agent': '365i-FAQ-Generator-Test-Script/1.0'
            },
            body: JSON.stringify(requestBody)
        });
        const responseTime = Date.now() - startTime;
        
        const data = await response.json();
        
        const logData = {
            timestamp: new Date().toISOString(),
            request: requestBody,
            response: data,
            status: response.status,
            responseTime: responseTime
        };
        
        saveToLog('faq-enhancer', logData);
        
        if (response.ok) {
            console.log(`✅ Success! Enhanced ${data.enhancedFaqs?.length || 0} FAQs in ${responseTime}ms`);
            if (data.enhancedFaqs && data.enhancedFaqs.length > 0) {
                console.log(`Enhanced answer: ${data.enhancedFaqs[0].answer.substring(0, 100)}...`);
            }
        } else {
            console.log(`❌ Error: ${response.status} - ${data.error || 'Unknown error'}`);
        }
        
        return { success: response.ok, data, responseTime };
    } catch (error) {
        console.error(`❌ Failed to test FAQ Enhancer: ${error.message}`);
        return { success: false, error: error.message };
    }
}

// Test the SEO Analyzer Worker
async function testSeoAnalyzer() {
    console.log('\n🔍 Testing SEO Analyzer Worker...');
    
    // Create some sample FAQs
    const faqs = [];
    const count = Math.floor(Math.random() * 5) + 3; // 3-7 FAQs
    
    for (let i = 0; i < count; i++) {
        faqs.push({
            question: `What is ${getRandomItem(SAMPLE_TOPICS)}?`,
            answer: getRandomItem(SAMPLE_CONTENT).substring(0, 200)
        });
    }
    
    const requestBody = {
        faqs: faqs,
        targetKeyword: getRandomItem(SAMPLE_TOPICS),
        clientId: 'test-script'
    };
    
    console.log(`Requesting SEO analysis for ${faqs.length} FAQs`);
    
    try {
        const startTime = Date.now();
        const response = await fetch(CONFIG.workers.seoAnalyzer, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'User-Agent': '365i-FAQ-Generator-Test-Script/1.0'
            },
            body: JSON.stringify(requestBody)
        });
        const responseTime = Date.now() - startTime;
        
        const data = await response.json();
        
        const logData = {
            timestamp: new Date().toISOString(),
            request: requestBody,
            response: data,
            status: response.status,
            responseTime: responseTime
        };
        
        saveToLog('seo-analyzer', logData);
        
        if (response.ok) {
            console.log(`✅ Success! Analyzed SEO in ${responseTime}ms`);
            if (data.score) {
                console.log(`SEO Score: ${data.score}/100`);
            }
            if (data.suggestions && data.suggestions.length > 0) {
                console.log(`Sample suggestion: ${data.suggestions[0]}`);
            }
        } else {
            console.log(`❌ Error: ${response.status} - ${data.error || 'Unknown error'}`);
        }
        
        return { success: response.ok, data, responseTime };
    } catch (error) {
        console.error(`❌ Failed to test SEO Analyzer: ${error.message}`);
        return { success: false, error: error.message };
    }
}

// Test the FAQ Extractor Worker
async function testFaqExtractor() {
    console.log('\n🔍 Testing FAQ Extractor Worker...');
    
    const url = getRandomItem(SAMPLE_URLS);
    
    const requestBody = {
        url: url,
        clientId: 'test-script'
    };
    
    console.log(`Requesting FAQ extraction from: ${url}`);
    
    try {
        const startTime = Date.now();
        const response = await fetch(CONFIG.workers.faqExtractor, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'User-Agent': '365i-FAQ-Generator-Test-Script/1.0'
            },
            body: JSON.stringify(requestBody)
        });
        const responseTime = Date.now() - startTime;
        
        const data = await response.json();
        
        const logData = {
            timestamp: new Date().toISOString(),
            request: requestBody,
            response: data,
            status: response.status,
            responseTime: responseTime
        };
        
        saveToLog('faq-extractor', logData);
        
        if (response.ok) {
            console.log(`✅ Success! Extracted content in ${responseTime}ms`);
            if (data.content) {
                console.log(`Content length: ${data.content.length} characters`);
                console.log(`Sample: ${data.content.substring(0, 100)}...`);
            }
            if (data.extractedFaqs && data.extractedFaqs.length > 0) {
                console.log(`Extracted ${data.extractedFaqs.length} FAQs`);
                console.log(`Sample FAQ: ${data.extractedFaqs[0].question}`);
            }
        } else {
            console.log(`❌ Error: ${response.status} - ${data.error || 'Unknown error'}`);
        }
        
        return { success: response.ok, data, responseTime };
    } catch (error) {
        console.error(`❌ Failed to test FAQ Extractor: ${error.message}`);
        return { success: false, error: error.message };
    }
}

// Test the Topic Generator Worker
async function testTopicGenerator() {
    console.log('\n📋 Testing Topic Generator Worker...');
    
    const url = getRandomItem(SAMPLE_URLS);
    
    const requestBody = {
        url: url,
        count: Math.floor(Math.random() * 5) + 3, // 3-7 topics
        clientId: 'test-script'
    };
    
    console.log(`Requesting ${requestBody.count} topics for URL: ${url}`);
    
    try {
        const startTime = Date.now();
        const response = await fetch(CONFIG.workers.topicGenerator, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'User-Agent': '365i-FAQ-Generator-Test-Script/1.0'
            },
            body: JSON.stringify(requestBody)
        });
        const responseTime = Date.now() - startTime;
        
        const data = await response.json();
        
        const logData = {
            timestamp: new Date().toISOString(),
            request: requestBody,
            response: data,
            status: response.status,
            responseTime: responseTime
        };
        
        saveToLog('topic-generator', logData);
        
        if (response.ok) {
            console.log(`✅ Success! Generated ${data.topics?.length || 0} topics in ${responseTime}ms`);
            if (data.topics && data.topics.length > 0) {
                console.log(`Sample topic: ${data.topics[0]}`);
            }
        } else {
            console.log(`❌ Error: ${response.status} - ${data.error || 'Unknown error'}`);
        }
        
        return { success: response.ok, data, responseTime };
    } catch (error) {
        console.error(`❌ Failed to test Topic Generator: ${error.message}`);
        return { success: false, error: error.message };
    }
}

// Run a complete end-to-end test workflow
async function testCompleteWorkflow() {
    console.log('\n🔄 Testing Complete End-to-End Workflow...');
    
    try {
        // Step 1: Extract content from a URL
        console.log('\nStep 1: Extracting content from URL...');
        const extractResult = await testFaqExtractor();
        if (!extractResult.success) {
            console.log('❌ Workflow stopped due to extraction failure');
            return false;
        }
        
        await sleep(CONFIG.delay);
        
        // Step 2: Generate topics from the extracted content
        console.log('\nStep 2: Generating topics from content...');
        const topicResult = await testTopicGenerator();
        if (!topicResult.success || !topicResult.data.topics || topicResult.data.topics.length === 0) {
            console.log('❌ Workflow stopped due to topic generation failure');
            return false;
        }
        
        await sleep(CONFIG.delay);
        
        // Step 3: Generate questions for a topic
        console.log('\nStep 3: Generating questions for a topic...');
        const questionResult = await testQuestionGenerator();
        if (!questionResult.success || !questionResult.data.questions || questionResult.data.questions.length === 0) {
            console.log('❌ Workflow stopped due to question generation failure');
            return false;
        }
        
        await sleep(CONFIG.delay);
        
        // Step 4: Generate answers for the questions
        console.log('\nStep 4: Generating answers for questions...');
        const answerResult = await testAnswerGenerator(questionResult.data.questions.slice(0, 3));
        if (!answerResult.success || !answerResult.data.faqs || answerResult.data.faqs.length === 0) {
            console.log('❌ Workflow stopped due to answer generation failure');
            return false;
        }
        
        await sleep(CONFIG.delay);
        
        // Step 5: Enhance the FAQs
        console.log('\nStep 5: Enhancing FAQs...');
        const enhanceResult = await testFaqEnhancer();
        if (!enhanceResult.success) {
            console.log('❌ Workflow stopped due to enhancement failure');
            return false;
        }
        
        await sleep(CONFIG.delay);
        
        // Step 6: Analyze SEO
        console.log('\nStep 6: Analyzing SEO...');
        const seoResult = await testSeoAnalyzer();
        if (!seoResult.success) {
            console.log('❌ Workflow stopped due to SEO analysis failure');
            return false;
        }
        
        console.log('\n✅ Complete workflow test completed successfully!');
        return true;
    } catch (error) {
        console.error(`❌ Complete workflow test failed: ${error.message}`);
        return false;
    }
}

// Main test function
async function runTests() {
    console.log('🧪 Starting 365i AI FAQ Generator Worker Tests');
    console.log(`🔢 Running ${CONFIG.iterations} iterations with ${CONFIG.delay}ms delay between requests`);
    console.log('=========================================================');
    
    const stats = {
        total: 0,
        successful: 0,
        failed: 0,
        startTime: Date.now(),
        endTime: null,
        workerStats: {}
    };
    
    // Initialize worker stats
    Object.keys(CONFIG.workers).forEach(worker => {
        stats.workerStats[worker] = {
            total: 0,
            successful: 0,
            failed: 0,
            avgResponseTime: 0,
            totalResponseTime: 0
        };
    });
    
    // Run individual tests for each iteration
    for (let i = 0; i < CONFIG.iterations; i++) {
        console.log(`\n=========================================================`);
        console.log(`🔄 Iteration ${i + 1} of ${CONFIG.iterations}`);
        
        // Randomly select a test to run
        const testFunctions = [
            testQuestionGenerator,
            testAnswerGenerator,
            testFaqEnhancer,
            testSeoAnalyzer,
            testFaqExtractor,
            testTopicGenerator
        ];
        
        // Every 5th iteration, run the complete workflow instead
        const selectedTest = (i % 5 === 0) ? testCompleteWorkflow : getRandomItem(testFunctions);
        
        try {
            let result;
            let workerType;
            
            // Determine which worker is being tested
            if (selectedTest === testQuestionGenerator) {
                workerType = 'questionGenerator';
            } else if (selectedTest === testAnswerGenerator) {
                workerType = 'answerGenerator';
            } else if (selectedTest === testFaqEnhancer) {
                workerType = 'faqEnhancer';
            } else if (selectedTest === testSeoAnalyzer) {
                workerType = 'seoAnalyzer';
            } else if (selectedTest === testFaqExtractor) {
                workerType = 'faqExtractor';
            } else if (selectedTest === testTopicGenerator) {
                workerType = 'topicGenerator';
            } else {
                workerType = 'completeWorkflow';
            }
            
            // Run the selected test
            result = await selectedTest();
            
            // Update stats
            stats.total++;
            if (result.success) {
                stats.successful++;
                if (workerType !== 'completeWorkflow') {
                    stats.workerStats[workerType].successful++;
                    stats.workerStats[workerType].total++;
                    stats.workerStats[workerType].totalResponseTime += result.responseTime || 0;
                    stats.workerStats[workerType].avgResponseTime = 
                        stats.workerStats[workerType].totalResponseTime / stats.workerStats[workerType].successful;
                }
            } else {
                stats.failed++;
                if (workerType !== 'completeWorkflow') {
                    stats.workerStats[workerType].failed++;
                    stats.workerStats[workerType].total++;
                }
            }
            
            // Wait before next test
            if (i < CONFIG.iterations - 1) {
                console.log(`Waiting ${CONFIG.delay}ms before next test...`);
                await sleep(CONFIG.delay);
            }
        } catch (error) {
            console.error(`❌ Test failed with error: ${error.message}`);
            stats.total++;
            stats.failed++;
        }
    }
    
    // Calculate final stats
    stats.endTime = Date.now();
    stats.duration = (stats.endTime - stats.startTime) / 1000; // in seconds
    stats.successRate = (stats.successful / stats.total) * 100;
    
    // Print summary
    console.log('\n=========================================================');
    console.log('📊 Test Summary');
    console.log('=========================================================');
    console.log(`Total Tests: ${stats.total}`);
    console.log(`Successful: ${stats.successful} (${stats.successRate.toFixed(2)}%)`);
    console.log(`Failed: ${stats.failed}`);
    console.log(`Duration: ${stats.duration.toFixed(2)} seconds`);
    
    console.log('\n📊 Worker Statistics');
    Object.keys(stats.workerStats).forEach(worker => {
        const workerStat = stats.workerStats[worker];
        if (workerStat.total > 0) {
            console.log(`\n${worker}:`);
            console.log(`  Total Requests: ${workerStat.total}`);
            console.log(`  Successful: ${workerStat.successful} (${((workerStat.successful / workerStat.total) * 100).toFixed(2)}%)`);
            console.log(`  Failed: ${workerStat.failed}`);
            console.log(`  Avg Response Time: ${workerStat.avgResponseTime.toFixed(2)}ms`);
        }
    });
    
    // Save stats to file
    const logDir = ensureLogDir();
    const statsFile = path.join(logDir, `test-stats-${new Date().toISOString().replace(/:/g, '-')}.json`);
    fs.writeFileSync(statsFile, JSON.stringify(stats, null, 2));
    console.log(`\nDetailed stats saved to ${statsFile}`);
}

// Start the tests
runTests().catch(error => {
    console.error('❌ Test runner failed:', error);
});