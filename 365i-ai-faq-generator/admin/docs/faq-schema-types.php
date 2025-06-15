<?php
/**
 * FAQ Schema Types Documentation
 *
 * Comprehensive documentation for all supported FAQ schema formats
 *
 * @package    FAQ_AI_Generator
 * @subpackage FAQ_AI_Generator/admin/docs
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="faq-ai-documentation schema-types">
    <div class="faq-ai-doc-header">
        <h1><?php _e('FAQ AI Generator - Schema Types', 'faq-ai-generator'); ?></h1>
        <p class="faq-ai-doc-description"><?php _e('A comprehensive guide to the different schema formats supported by the FAQ AI Generator.', 'faq-ai-generator'); ?></p>
    </div>
    
    <div class="faq-ai-doc-navigation">
        <ul>
            <li><a href="#schema-overview"><?php _e('Schema Overview', 'faq-ai-generator'); ?></a></li>
            <li><a href="#json-ld"><?php _e('JSON-LD', 'faq-ai-generator'); ?></a></li>
            <li><a href="#microdata"><?php _e('Microdata', 'faq-ai-generator'); ?></a></li>
            <li><a href="#rdfa"><?php _e('RDFa', 'faq-ai-generator'); ?></a></li>
            <li><a href="#html-only"><?php _e('HTML Only', 'faq-ai-generator'); ?></a></li>
            <li><a href="#seo-benefits"><?php _e('SEO Benefits', 'faq-ai-generator'); ?></a></li>
            <li><a href="#implementation"><?php _e('Implementation Guide', 'faq-ai-generator'); ?></a></li>
        </ul>
    </div>
    
    <div class="faq-ai-doc-section" id="schema-overview">
        <h2><?php _e('Schema Overview', 'faq-ai-generator'); ?></h2>
        <p><?php _e('Schema markup is a form of structured data that helps search engines understand the content of your website. For FAQs, schema markup can help search engines display your questions and answers directly in search results, potentially increasing your visibility and click-through rates.', 'faq-ai-generator'); ?></p>
        
        <p><?php _e('The FAQ AI Generator supports four different formats for schema markup:', 'faq-ai-generator'); ?></p>
        <ul>
            <li><strong><?php _e('JSON-LD', 'faq-ai-generator'); ?></strong>: <?php _e('A JavaScript notation embedded in a script tag.', 'faq-ai-generator'); ?></li>
            <li><strong><?php _e('Microdata', 'faq-ai-generator'); ?></strong>: <?php _e('HTML attributes embedded directly in your content.', 'faq-ai-generator'); ?></li>
            <li><strong><?php _e('RDFa', 'faq-ai-generator'); ?></strong>: <?php _e('Resource Description Framework in Attributes, an extension to HTML5.', 'faq-ai-generator'); ?></li>
            <li><strong><?php _e('HTML Only', 'faq-ai-generator'); ?></strong>: <?php _e('Clean HTML without schema markup.', 'faq-ai-generator'); ?></li>
        </ul>
        
        <div class="faq-ai-doc-note">
            <p><strong><?php _e('Note:', 'faq-ai-generator'); ?></strong> <?php _e('Google recommends using JSON-LD for structured data whenever possible, as it\'s the easiest for them to parse and is less prone to errors.', 'faq-ai-generator'); ?></p>
        </div>
    </div>
    
    <div class="faq-ai-doc-section" id="json-ld">
        <h2><?php _e('JSON-LD', 'faq-ai-generator'); ?></h2>
        <p><?php _e('JSON-LD (JavaScript Object Notation for Linked Data) is a method of encoding Linked Data using JSON. It\'s the recommended format by Google for structured data.', 'faq-ai-generator'); ?></p>
        
        <h3><?php _e('Advantages', 'faq-ai-generator'); ?></h3>
        <ul>
            <li><?php _e('Doesn\'t interfere with your HTML markup', 'faq-ai-generator'); ?></li>
            <li><?php _e('Easy to implement (just add a script tag to your page)', 'faq-ai-generator'); ?></li>
            <li><?php _e('Preferred by Google and other search engines', 'faq-ai-generator'); ?></li>
            <li><?php _e('Less prone to errors when updating content', 'faq-ai-generator'); ?></li>
        </ul>
        
        <h3><?php _e('Example', 'faq-ai-generator'); ?></h3>
        <pre><code>&lt;script type="application/ld+json"&gt;
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What is FAQ AI Generator?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "FAQ AI Generator is a WordPress plugin that uses AI to help you create, manage, and optimize FAQs for your website."
      }
    },
    {
      "@type": "Question",
      "name": "How does it work?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "It uses Cloudflare Workers AI to generate questions, answers, and optimize your FAQs for search engines."
      }
    }
  ]
}
&lt;/script&gt;</code></pre>
        
        <h3><?php _e('Implementation', 'faq-ai-generator'); ?></h3>
        <ol>
            <li><?php _e('Generate the JSON-LD code using the Export tab in the FAQ AI Generator.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Copy the generated code.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Add it to the <code>&lt;head&gt;</code> section of your page, or use a schema plugin to add it to your page.', 'faq-ai-generator'); ?></li>
        </ol>
    </div>
    
    <div class="faq-ai-doc-section" id="microdata">
        <h2><?php _e('Microdata', 'faq-ai-generator'); ?></h2>
        <p><?php _e('Microdata is a set of HTML attributes that embed structured data directly into your HTML content.', 'faq-ai-generator'); ?></p>
        
        <h3><?php _e('Advantages', 'faq-ai-generator'); ?></h3>
        <ul>
            <li><?php _e('Directly associates the schema with the visible content', 'faq-ai-generator'); ?></li>
            <li><?php _e('Supported by all major search engines', 'faq-ai-generator'); ?></li>
            <li><?php _e('No need for separate script tags', 'faq-ai-generator'); ?></li>
        </ul>
        
        <h3><?php _e('Example', 'faq-ai-generator'); ?></h3>
        <pre><code>&lt;div itemscope itemtype="https://schema.org/FAQPage"&gt;
  &lt;div itemscope itemprop="mainEntity" itemtype="https://schema.org/Question"&gt;
    &lt;h3 itemprop="name"&gt;What is FAQ AI Generator?&lt;/h3&gt;
    &lt;div itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer"&gt;
      &lt;div itemprop="text"&gt;
        &lt;p&gt;FAQ AI Generator is a WordPress plugin that uses AI to help you create, manage, and optimize FAQs for your website.&lt;/p&gt;
      &lt;/div&gt;
    &lt;/div&gt;
  &lt;/div&gt;
  
  &lt;div itemscope itemprop="mainEntity" itemtype="https://schema.org/Question"&gt;
    &lt;h3 itemprop="name"&gt;How does it work?&lt;/h3&gt;
    &lt;div itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer"&gt;
      &lt;div itemprop="text"&gt;
        &lt;p&gt;It uses Cloudflare Workers AI to generate questions, answers, and optimize your FAQs for search engines.&lt;/p&gt;
      &lt;/div&gt;
    &lt;/div&gt;
  &lt;/div&gt;
&lt;/div&gt;</code></pre>
        
        <h3><?php _e('Implementation', 'faq-ai-generator'); ?></h3>
        <ol>
            <li><?php _e('Generate the Microdata HTML using the Export tab in the FAQ AI Generator.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Copy the generated HTML.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Replace your existing FAQ HTML with this schema-enhanced version.', 'faq-ai-generator'); ?></li>
        </ol>
    </div>
    
    <div class="faq-ai-doc-section" id="rdfa">
        <h2><?php _e('RDFa', 'faq-ai-generator'); ?></h2>
        <p><?php _e('RDFa (Resource Description Framework in Attributes) is an extension to HTML5 that helps you mark up structured data.', 'faq-ai-generator'); ?></p>
        
        <h3><?php _e('Advantages', 'faq-ai-generator'); ?></h3>
        <ul>
            <li><?php _e('More flexible than Microdata', 'faq-ai-generator'); ?></li>
            <li><?php _e('Can express more complex relationships', 'faq-ai-generator'); ?></li>
            <li><?php _e('Supported by major search engines', 'faq-ai-generator'); ?></li>
        </ul>
        
        <h3><?php _e('Example', 'faq-ai-generator'); ?></h3>
        <pre><code>&lt;div vocab="https://schema.org/" typeof="FAQPage"&gt;
  &lt;div property="mainEntity" typeof="Question"&gt;
    &lt;h3 property="name"&gt;What is FAQ AI Generator?&lt;/h3&gt;
    &lt;div property="acceptedAnswer" typeof="Answer"&gt;
      &lt;div property="text"&gt;
        &lt;p&gt;FAQ AI Generator is a WordPress plugin that uses AI to help you create, manage, and optimize FAQs for your website.&lt;/p&gt;
      &lt;/div&gt;
    &lt;/div&gt;
  &lt;/div&gt;
  
  &lt;div property="mainEntity" typeof="Question"&gt;
    &lt;h3 property="name"&gt;How does it work?&lt;/h3&gt;
    &lt;div property="acceptedAnswer" typeof="Answer"&gt;
      &lt;div property="text"&gt;
        &lt;p&gt;It uses Cloudflare Workers AI to generate questions, answers, and optimize your FAQs for search engines.&lt;/p&gt;
      &lt;/div&gt;
    &lt;/div&gt;
  &lt;/div&gt;
&lt;/div&gt;</code></pre>
        
        <h3><?php _e('Implementation', 'faq-ai-generator'); ?></h3>
        <ol>
            <li><?php _e('Generate the RDFa HTML using the Export tab in the FAQ AI Generator.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Copy the generated HTML.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Replace your existing FAQ HTML with this schema-enhanced version.', 'faq-ai-generator'); ?></li>
        </ol>
    </div>
    
    <div class="faq-ai-doc-section" id="html-only">
        <h2><?php _e('HTML Only', 'faq-ai-generator'); ?></h2>
        <p><?php _e('The HTML Only option provides clean HTML markup without any schema data. This is useful if you want to use your own schema implementation or if you don\'t need schema markup.', 'faq-ai-generator'); ?></p>
        
        <h3><?php _e('Advantages', 'faq-ai-generator'); ?></h3>
        <ul>
            <li><?php _e('Clean, lightweight HTML', 'faq-ai-generator'); ?></li>
            <li><?php _e('Easy to customize with your own CSS', 'faq-ai-generator'); ?></li>
            <li><?php _e('Can be used with your own schema implementation', 'faq-ai-generator'); ?></li>
        </ul>
        
        <h3><?php _e('Example', 'faq-ai-generator'); ?></h3>
        <pre><code>&lt;div class="faq-container"&gt;
  &lt;div class="faq-item"&gt;
    &lt;h3 class="faq-question"&gt;What is FAQ AI Generator?&lt;/h3&gt;
    &lt;div class="faq-answer"&gt;
      &lt;p&gt;FAQ AI Generator is a WordPress plugin that uses AI to help you create, manage, and optimize FAQs for your website.&lt;/p&gt;
    &lt;/div&gt;
  &lt;/div&gt;
  
  &lt;div class="faq-item"&gt;
    &lt;h3 class="faq-question"&gt;How does it work?&lt;/h3&gt;
    &lt;div class="faq-answer"&gt;
      &lt;p&gt;It uses Cloudflare Workers AI to generate questions, answers, and optimize your FAQs for search engines.&lt;/p&gt;
    &lt;/div&gt;
  &lt;/div&gt;
&lt;/div&gt;</code></pre>
        
        <h3><?php _e('Implementation', 'faq-ai-generator'); ?></h3>
        <ol>
            <li><?php _e('Generate the HTML using the Export tab in the FAQ AI Generator.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Copy the generated HTML.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Add it to your page where you want the FAQs to appear.', 'faq-ai-generator'); ?></li>
        </ol>
    </div>
    
    <div class="faq-ai-doc-section" id="seo-benefits">
        <h2><?php _e('SEO Benefits', 'faq-ai-generator'); ?></h2>
        <p><?php _e('Using schema markup for your FAQs can provide several SEO benefits:', 'faq-ai-generator'); ?></p>
        
        <h3><?php _e('Rich Results in Search', 'faq-ai-generator'); ?></h3>
        <p><?php _e('When you implement FAQ schema correctly, Google may display your FAQs directly in search results as rich results. This can increase your visibility and click-through rates.', 'faq-ai-generator'); ?></p>
        <img src="<?php echo plugin_dir_url(dirname(dirname(__FILE__))) . 'admin/images/faq-rich-results.png'; ?>" alt="FAQ Rich Results Example" class="faq-ai-doc-image">
        
        <h3><?php _e('Voice Search Optimization', 'faq-ai-generator'); ?></h3>
        <p><?php _e('FAQ schema can help voice assistants like Google Assistant, Siri, and Alexa find and read your answers to users\' questions.', 'faq-ai-generator'); ?></p>
        
        <h3><?php _e('Increased SERP Real Estate', 'faq-ai-generator'); ?></h3>
        <p><?php _e('FAQ rich results take up more space in search results, pushing competitors further down the page.', 'faq-ai-generator'); ?></p>
        
        <h3><?php _e('Improved Relevance Signals', 'faq-ai-generator'); ?></h3>
        <p><?php _e('Schema markup helps search engines better understand your content, potentially improving your rankings for relevant queries.', 'faq-ai-generator'); ?></p>
    </div>
    
    <div class="faq-ai-doc-section" id="implementation">
        <h2><?php _e('Implementation Guide', 'faq-ai-generator'); ?></h2>
        <p><?php _e('Follow these steps to implement FAQ schema on your website:', 'faq-ai-generator'); ?></p>
        
        <h3><?php _e('Step 1: Create Your FAQs', 'faq-ai-generator'); ?></h3>
        <p><?php _e('Use the FAQ AI Generator to create and optimize your FAQs.', 'faq-ai-generator'); ?></p>
        
        <h3><?php _e('Step 2: Choose a Schema Format', 'faq-ai-generator'); ?></h3>
        <p><?php _e('Select the schema format that best suits your needs. We recommend JSON-LD for most users.', 'faq-ai-generator'); ?></p>
        
        <h3><?php _e('Step 3: Generate the Schema', 'faq-ai-generator'); ?></h3>
        <ol>
            <li><?php _e('Go to the Export tab in the FAQ AI Generator.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Select your desired schema format.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Enter the base URL for your FAQ page (optional).', 'faq-ai-generator'); ?></li>
            <li><?php _e('Click "Generate Schema" to create the schema code.', 'faq-ai-generator'); ?></li>
        </ol>
        
        <h3><?php _e('Step 4: Implement the Schema', 'faq-ai-generator'); ?></h3>
        <p><?php _e('For JSON-LD:', 'faq-ai-generator'); ?></p>
        <ol>
            <li><?php _e('Copy the generated JSON-LD code.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Add it to the <code>&lt;head&gt;</code> section of your page, or use a schema plugin to add it to your page.', 'faq-ai-generator'); ?></li>
        </ol>
        
        <p><?php _e('For Microdata or RDFa:', 'faq-ai-generator'); ?></p>
        <ol>
            <li><?php _e('Copy the generated HTML code.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Replace your existing FAQ HTML with this schema-enhanced version.', 'faq-ai-generator'); ?></li>
        </ol>
        
        <h3><?php _e('Step 5: Validate Your Schema', 'faq-ai-generator'); ?></h3>
        <p><?php _e('Use Google\'s Rich Results Test to validate your schema implementation:', 'faq-ai-generator'); ?></p>
        <ol>
            <li><?php _e('Go to <a href="https://search.google.com/test/rich-results" target="_blank">https://search.google.com/test/rich-results</a>', 'faq-ai-generator'); ?></li>
            <li><?php _e('Enter your URL or paste your code.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Check for any errors or warnings and fix them if necessary.', 'faq-ai-generator'); ?></li>
        </ol>
        
        <h3><?php _e('Step 6: Monitor Performance', 'faq-ai-generator'); ?></h3>
        <p><?php _e('Use Google Search Console to monitor how your FAQs are performing in search results:', 'faq-ai-generator'); ?></p>
        <ol>
            <li><?php _e('Go to Google Search Console.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Navigate to the "Performance" report.', 'faq-ai-generator'); ?></li>
            <li><?php _e('Filter by "Search Appearance" > "FAQ".', 'faq-ai-generator'); ?></li>
            <li><?php _e('Analyze the performance of your FAQ rich results.', 'faq-ai-generator'); ?></li>
        </ol>
        
        <div class="faq-ai-doc-note">
            <p><strong><?php _e('Note:', 'faq-ai-generator'); ?></strong> <?php _e('It may take some time for Google to index your schema and start showing rich results.', 'faq-ai-generator'); ?></p>
        </div>
    </div>
</div>