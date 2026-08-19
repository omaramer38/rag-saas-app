<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\SubscriptionPlan;
use App\Models\SiteSetting;
use App\Models\GuidePage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Create Admin User
        User::create([
            'name' => 'Admin',
            'email' => 'admin@doctorchat.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Create Demo Doctor
        User::create([
            'name' => 'Dr. Ahmed',
            'email' => 'doctor@doctorchat.com',
            'password' => Hash::make('password'),
            'role' => 'doctor',
            'is_active' => true,
            'phone' => '+201000000000',
            'email_verified_at' => now(),
        ]);

        // Create Subscription Plans
        SubscriptionPlan::create([
            'name' => 'Basic',
            'description' => 'Perfect for getting started with AI-powered medical assistance.',
            'price' => 99,
            'duration_days' => 30,
            'features' => ['1 PDF Upload', '100 Chat Messages', 'Email Support', 'Chat History'],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        SubscriptionPlan::create([
            'name' => 'Pro',
            'description' => 'For medical professionals who need more power.',
            'price' => 199,
            'duration_days' => 30,
            'features' => ['3 PDF Uploads', 'Unlimited Chat', 'Priority Support', 'Chat History', 'Export Chat'],
            'is_active' => true,
            'sort_order' => 2,
        ]);

        SubscriptionPlan::create([
            'name' => 'Enterprise',
            'description' => 'For clinics and hospitals with advanced needs.',
            'price' => 499,
            'duration_days' => 30,
            'features' => ['Unlimited PDFs', 'Unlimited Chat', '24/7 Support', 'Custom Training', 'API Access', 'Team Management'],
            'is_active' => true,
            'sort_order' => 3,
        ]);

        // Create Default Settings
        SiteSetting::set('site_name', 'DoctorChat');
        SiteSetting::set('site_description', 'AI-Powered Medical Assistant Platform');
        SiteSetting::set('contact_email', 'support@doctorchat.com');
        SiteSetting::set('contact_phone', '+201000000000');

        // Create Default Guide Pages
        GuidePage::create([
            'title' => 'Getting Started',
            'slug' => 'getting-started',
            'content' => '<h2>Welcome to DoctorChat!</h2><p>Follow these steps to get started:</p><ol><li>Create your account</li><li>Choose a subscription plan</li><li>Upload your PDF research</li><li>Start chatting with your AI assistant</li></ol>',
            'category' => 'Basics',
            'sort_order' => 1,
            'is_published' => true,
            'created_by' => 1,
        ]);

        GuidePage::create([
            'title' => 'Uploading Research Files',
            'slug' => 'uploading-files',
            'content' => '<h2>How to Upload PDF Files</h2><p>Go to the Files section in your dashboard. You can drag and drop a PDF or click to browse. Note that uploading a new file will replace your existing one.</p><p>Supported formats: PDF (max 50MB)</p>',
            'category' => 'Basics',
            'sort_order' => 2,
            'is_published' => true,
            'created_by' => 1,
        ]);

        GuidePage::create([
            'title' => 'Using the Chatbot',
            'slug' => 'using-chatbot',
            'content' => '<h2>How to Use the AI Chatbot</h2><p>Your chatbot is trained on your uploaded research. Simply type your question and get instant answers.</p><ul><li>Create new chats for different topics</li><li>View your chat history anytime</li><li>Ask specific questions about your research</li></ul>',
            'category' => 'Features',
            'sort_order' => 3,
            'is_published' => true,
            'created_by' => 1,
        ]);

        GuidePage::create([
            'title' => 'Managing Your Subscription',
            'slug' => 'managing-subscription',
            'content' => '<h2>Subscription Management</h2><p>View your current plan, expiration date, and upgrade or renew your subscription from the Subscription page in your dashboard.</p><p>Payments are processed securely through Paymob.</p>',
            'category' => 'Account',
            'sort_order' => 4,
            'is_published' => true,
            'created_by' => 1,
        ]);

        $this->command->info('Database seeded successfully!');
    }
}
