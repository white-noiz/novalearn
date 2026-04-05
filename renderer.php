<?php
defined('MOODLE_INTERNAL') || die();

use local_personalization\learner_profile;
use local_personalization\recommendation_engine;

class block_ai_dashboard_renderer {

    public function render_dashboard($userid) {
        global $DB;

        // =========================
        // 1. FETCH AI DATA
        // =========================
        $ai = $DB->get_record('ai_recommendation', ['userid'=>$userid]);

        if (!$ai) {
            // If no recommendation exists yet, generate one
            $ai_generated = recommendation_engine::generate($userid);

            $recommendation = $ai_generated['recommendation'] ?? 'No recommendation yet';
            $studytime = $ai_generated['studytime'] ?? 0;
            $engagement = $ai_generated['engagement'] ?? 'N/A';
            $cluster = $ai_generated['cluster'] ?? -1;
            $level = $ai_generated['level'] ?? 'Beginner';
        } else {
            // Use saved record
            $recommendation = $ai->recommendation;
            $studytime = $ai->studytime ?? 0;
            $engagement = $ai->engagement ?? 'N/A';
            $cluster = $ai->cluster;
            $level = $ai->level ?? 'Beginner';
        }

        // Get MBTI
        $mbti = learner_profile::get_mbti($userid) ?? 'Unknown';

        // =========================
        // 2. COURSES & PROGRESS
        // =========================
        $all_courses = [
            ['id'=>201,'name'=>'Calculus 101'],
            ['id'=>202,'name'=>'Data Science Intro'],
            ['id'=>203,'name'=>'AI Fundamentals'],
        ];

        // TODO: Replace with real user course progress
        $user_courses_progress = [
            201=>65, 202=>40, 203=>20
        ];

        // =========================
        // 3. RENDER HTML
        // =========================
        $html = '<link rel="stylesheet" href="blocks/ai_dashboard/styles.css">';
        $html .= '<div class="ai-dashboard">';

        // --- Learner Profile Card ---
        $html .= '<div class="ai-dashboard-card">';
        $html .= '<h4>👤 Learner Profile</h4>';
        $html .= '<p><strong>MBTI:</strong> '.$mbti.'</p>';
        $html .= '<p><strong>Cluster:</strong> '.$cluster.'</p>';
        $html .= '<p><strong>Level:</strong> '.$level.'</p>';
        $html .= '</div>';

        // --- Courses Progress ---
        foreach ($user_courses_progress as $cid=>$progress) {
            $course = array_filter($all_courses, fn($c)=>$c['id']==$cid);
            $course = array_values($course)[0];

            $html .= '<div class="ai-dashboard-card">';
            $html .= '<h4>'.$course['name'].'</h4>';
            $html .= '<div class="ai-dashboard-progress">';
            $html .= '<div class="ai-dashboard-progress-bar" style="width:'.$progress.'%"></div>';
            $html .= '</div>';
            $html .= '<small>Progress: '.$progress.'%</small>';
            $html .= '</div>';
        }

        // --- AI Recommendation Card ---
        $html .= '<div class="ai-dashboard-card">';
        $html .= '<h4>🤖 AI Recommendation</h4>';
        $html .= '<p>'.$recommendation.'</p>';
        $html .= '<small>Study Time: '.$studytime.' mins | Engagement: '.$engagement.'</small>';
        $html .= '</div>';

        // --- Suggested Courses Card ---
        $suggested_courses = $this->get_ai_courses($cluster);
        $html .= '<div class="ai-dashboard-card">';
        $html .= '<h4>📚 Suggested Courses</h4>';
        $html .= '<div class="ai-course-list">';
        foreach ($suggested_courses as $course) {
            $html .= '<button onclick="alert(\'Enrolled in '.$course.'\')">'.$course.'</button>';
        }
        $html .= '</div>';
        $html .= '</div>';

        // --- Animate progress bars ---
        $html .= '<script>
            document.querySelectorAll(".ai-dashboard-progress-bar").forEach(function(bar){
                var width = bar.style.width;
                bar.style.width = "0%";
                setTimeout(function(){ bar.style.width = width; }, 100);
            });
        </script>';

        $html .= '</div>';
        return $html;
    }

    // =========================
    // Map AI cluster → suggested courses
    // =========================
    private function get_ai_courses($cluster) {
        if ($cluster === 0) return ['Basic Mathematics', 'Intro to Data'];
        if ($cluster === 1) return ['Statistics', 'Data Science'];
        if ($cluster === 2) return ['Machine Learning', 'AI Engineering'];
        return ['General Learning Skills'];
    }
}