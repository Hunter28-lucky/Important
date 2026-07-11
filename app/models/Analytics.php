<?php
namespace App\Models;

class Analytics extends Model {
    /**
     * Log a template view event.
     */
    public function logView($templateId, $ip, $ua, $referrer, $sessionId) {
        $sql = "INSERT INTO analytics_views (template_id, visitor_ip, visitor_ua, referrer, session_id) 
                VALUES (:template_id, :ip, :ua, :referrer, :session_id)";
        return $this->query($sql, [
            'template_id' => $templateId,
            'ip' => $ip,
            'ua' => substr($ua, 0, 255),
            'referrer' => substr($referrer, 0, 255),
            'session_id' => $sessionId
        ]);
    }

    /**
     * Log a link click inside a template.
     */
    public function logClick($templateId, $linkUrl, $ip, $sessionId) {
        $sql = "INSERT INTO analytics_clicks (template_id, link_url, visitor_ip, session_id) 
                VALUES (:template_id, :link_url, :ip, :session_id)";
        return $this->query($sql, [
            'template_id' => $templateId,
            'link_url' => substr($linkUrl, 0, 255),
            'ip' => $ip,
            'session_id' => $sessionId
        ]);
    }

    /**
     * Retrieve aggregate summary statistics for the dashboard.
     */
    public function getSummaryStats() {
        $views = $this->fetch("SELECT COUNT(*) as count FROM analytics_views");
        $clicks = $this->fetch("SELECT COUNT(*) as count FROM analytics_clicks");
        $visitors = $this->fetch("SELECT COUNT(DISTINCT visitor_ip) as count FROM analytics_views");
        $templates = $this->fetch("SELECT COUNT(*) as count FROM templates");

        return [
            'total_views' => (int)($views['count'] ?? 0),
            'total_clicks' => (int)($clicks['count'] ?? 0),
            'unique_visitors' => (int)($visitors['count'] ?? 0),
            'total_templates' => (int)($templates['count'] ?? 0)
        ];
    }

    /**
     * Get templates ranked by total views.
     */
    public function getMostPopularTemplates($limit = 5) {
        $sql = "SELECT t.id, t.title, t.slug, t.status, 
                       COUNT(DISTINCT v.id) as views_count, 
                       COUNT(DISTINCT c.id) as clicks_count
                FROM templates t
                LEFT JOIN analytics_views v ON t.id = v.template_id
                LEFT JOIN analytics_clicks c ON t.id = c.template_id
                GROUP BY t.id
                ORDER BY views_count DESC
                LIMIT " . (int)$limit;
        return $this->fetchAll($sql);
    }

    /**
     * Get recently viewed templates with details.
     */
    public function getRecentlyViewed($limit = 5) {
        $sql = "SELECT t.id, t.title, t.slug, v.created_at as viewed_at, v.visitor_ip, v.referrer
                FROM analytics_views v
                JOIN templates t ON v.template_id = t.id
                ORDER BY v.created_at DESC
                LIMIT " . (int)$limit;
        return $this->fetchAll($sql);
    }

    /**
     * Fetch daily views for the last X days (default 7) for charting.
     */
    public function getViewsOverTime($days = 7) {
        $driver = $this->db->getAttribute(\PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $sql = "SELECT DATE(created_at) as view_date, COUNT(*) as view_count
                    FROM analytics_views
                    WHERE created_at >= datetime('now', '-' || :days || ' day')
                    GROUP BY DATE(created_at)
                    ORDER BY view_date ASC";
        } else {
            $sql = "SELECT DATE(created_at) as view_date, COUNT(*) as view_count
                    FROM analytics_views
                    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
                    GROUP BY DATE(created_at)
                    ORDER BY view_date ASC";
        }
        return $this->fetchAll($sql, ['days' => $days]);
    }

    /**
     * Get analytics metrics for a specific template.
     */
    public function getTemplateStats($templateId) {
        $views = $this->fetch("SELECT COUNT(*) as count FROM analytics_views WHERE template_id = :id", ['id' => $templateId]);
        $clicks = $this->fetch("SELECT COUNT(*) as count FROM analytics_clicks WHERE template_id = :id", ['id' => $templateId]);
        $visitors = $this->fetch("SELECT COUNT(DISTINCT visitor_ip) as count FROM analytics_views WHERE template_id = :id", ['id' => $templateId]);
        
        $topClicks = $this->fetchAll(
            "SELECT link_url, COUNT(*) as click_count 
             FROM analytics_clicks 
             WHERE template_id = :id 
             GROUP BY link_url 
             ORDER BY click_count DESC 
             LIMIT 5", 
            ['id' => $templateId]
        );

        return [
            'views' => (int)($views['count'] ?? 0),
            'clicks' => (int)($clicks['count'] ?? 0),
            'unique_visitors' => (int)($visitors['count'] ?? 0),
            'top_clicks' => $topClicks
        ];
    }
}
