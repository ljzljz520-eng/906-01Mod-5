<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/SearchHistory.php';
require_once __DIR__ . '/../models/Favorite.php';
require_once __DIR__ . '/../models/SecurityAdvisory.php';

class ApiController {
    private $db;
    private $searchHistory;
    private $favorite;
    private $securityAdvisory;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->searchHistory = new SearchHistory($this->db);
        $this->favorite = new Favorite($this->db);
        $this->securityAdvisory = new SecurityAdvisory($this->db);
    }

    // GET /health
    public function healthCheck() {
        echo json_encode([
            'status' => 'ok',
            'timestamp' => date('c'),
            'service' => 'Torrent Search API (PHP)'
        ]);
    }

    // GET /api/providers
    public function getProviders() {
        // Mocking providers since we use demo data
        echo json_encode([
            'success' => true,
            'data' => [
                'all' => ['1337x', 'Yts', 'ThePirateBay', 'Rarbg', 'Torrent9'],
                'active' => ['1337x', 'Yts']
            ]
        ]);
    }

    // GET /api/search/:keyword/:query/:page
    public function search($keyword, $query, $page = 1) {
        // Save history
        $this->searchHistory->keyword = $keyword;
        $this->searchHistory->query = $query;
        $this->searchHistory->create();

        // Generate demo data
        $results = $this->generateDemoData($query, $keyword, $page);

        // Match security advisories
        $advisories = $this->securityAdvisory->matchByKeyword($query);
        foreach ($advisories as &$advisory) {
            if (!empty($advisory['alternative_resources'])) {
                $advisory['alternative_resources'] = json_decode($advisory['alternative_resources'], true);
            }
        }

        // Inject security info into results
        foreach ($results as &$result) {
            $resultAdvisories = [];
            foreach ($advisories as $advisory) {
                $nameLower = strtolower($result['Name'] ?? $result['title'] ?? '');
                $componentLower = strtolower($advisory['component_name']);
                if (strpos($nameLower, $componentLower) !== false) {
                    $resultAdvisories[] = $advisory;
                }
            }
            if (!empty($resultAdvisories)) {
                $result['security_advisories'] = $resultAdvisories;
                $activeAdvisories = array_filter($resultAdvisories, function($a) {
                    return $a['status'] === 'active';
                });
                $result['has_active_security_issue'] = count($activeAdvisories) > 0;
                $result['highest_severity'] = $this->getHighestSeverity($resultAdvisories);
            } else {
                $result['security_advisories'] = [];
                $result['has_active_security_issue'] = false;
                $result['highest_severity'] = null;
            }
        }

        echo json_encode([
            'success' => true,
            'data' => $results,
            'meta' => [
                'keyword' => $keyword,
                'query' => $query,
                'page' => (int)$page,
                'count' => count($results),
                'demo' => true,
                'security_advisories_count' => count($advisories)
            ]
        ]);
    }

    // GET /api/history
    public function getHistory() {
        $limit = isset($_GET['limit']) ? $_GET['limit'] : 20;
        $history = $this->searchHistory->findAll($limit);

        echo json_encode([
            'success' => true,
            'data' => $history
        ]);
    }

    // DELETE /api/history
    public function clearHistory() {
        if ($this->searchHistory->deleteAll()) {
            echo json_encode(['success' => true, 'message' => '搜索历史已清空']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => '清空搜索历史失败']);
        }
    }

    // POST /api/favorites
    public function addFavorite() {
        $data = json_decode(file_get_contents("php://input"));

        if (!$data) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
            return;
        }

        // Check existing
        if ($this->favorite->findOneByMagnet($data->magnet)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => '该资源已在收藏列表中']);
            return;
        }

        $this->favorite->name = $data->name;
        $this->favorite->magnet = $data->magnet;
        $this->favorite->size = $data->size;
        $this->favorite->seeders = $data->seeders ?? 0;
        $this->favorite->leechers = $data->leechers ?? 0;
        $this->favorite->category = $data->category;
        $this->favorite->source = $data->source;

        if ($this->favorite->create()) {
            echo json_encode([
                'success' => true, 
                'message' => '收藏成功', 
                'data' => $this->favorite
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => '收藏失败']);
        }
    }

    // GET /api/favorites
    public function getFavorites() {
        $favorites = $this->favorite->findAll();
        echo json_encode([
            'success' => true,
            'data' => $favorites
        ]);
    }

    // DELETE /api/favorites/:id
    public function deleteFavorite($id) {
        if ($this->favorite->delete($id)) {
            echo json_encode(['success' => true, 'message' => '删除成功']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => '收藏不存在']);
        }
    }

    // GET /api/security/advisories
    public function getSecurityAdvisories() {
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        $severity = isset($_GET['severity']) ? $_GET['severity'] : null;
        
        $advisories = $this->securityAdvisory->findAll($status, $severity);
        
        foreach ($advisories as &$advisory) {
            if (!empty($advisory['alternative_resources'])) {
                $advisory['alternative_resources'] = json_decode($advisory['alternative_resources'], true);
            }
        }

        echo json_encode([
            'success' => true,
            'data' => $advisories
        ]);
    }

    // GET /api/security/advisories/:id
    public function getSecurityAdvisory($id) {
        $advisory = $this->securityAdvisory->findOne($id);
        
        if ($advisory) {
            if (!empty($advisory['alternative_resources'])) {
                $advisory['alternative_resources'] = json_decode($advisory['alternative_resources'], true);
            }
            echo json_encode([
                'success' => true,
                'data' => $advisory
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => '安全公告不存在']);
        }
    }

    // POST /api/security/advisories
    public function createSecurityAdvisory() {
        $data = json_decode(file_get_contents("php://input"));

        if (!$data) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
            return;
        }

        $this->securityAdvisory->component_name = $data->component_name;
        $this->securityAdvisory->affected_versions = $data->affected_versions;
        $this->securityAdvisory->fixed_version = $data->fixed_version ?? '';
        $this->securityAdvisory->severity = $data->severity ?? 'medium';
        $this->securityAdvisory->title = $data->title;
        $this->securityAdvisory->description = $data->description ?? '';
        $this->securityAdvisory->alternative_resources = isset($data->alternative_resources) 
            ? json_encode($data->alternative_resources) 
            : json_encode([]);
        $this->securityAdvisory->status = $data->status ?? 'active';

        if ($this->securityAdvisory->create()) {
            echo json_encode([
                'success' => true,
                'message' => '安全公告创建成功',
                'data' => ['id' => $this->securityAdvisory->id]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => '创建安全公告失败']);
        }
    }

    // PUT /api/security/advisories/:id
    public function updateSecurityAdvisory($id) {
        $data = json_decode(file_get_contents("php://input"));

        if (!$data) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
            return;
        }

        $existing = $this->securityAdvisory->findOne($id);
        if (!$existing) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => '安全公告不存在']);
            return;
        }

        $this->securityAdvisory->component_name = $data->component_name;
        $this->securityAdvisory->affected_versions = $data->affected_versions;
        $this->securityAdvisory->fixed_version = $data->fixed_version ?? '';
        $this->securityAdvisory->severity = $data->severity ?? 'medium';
        $this->securityAdvisory->title = $data->title;
        $this->securityAdvisory->description = $data->description ?? '';
        $this->securityAdvisory->alternative_resources = isset($data->alternative_resources) 
            ? json_encode($data->alternative_resources) 
            : json_encode([]);

        if ($this->securityAdvisory->update($id)) {
            echo json_encode([
                'success' => true,
                'message' => '安全公告更新成功'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => '更新安全公告失败']);
        }
    }

    // PATCH /api/security/advisories/:id/status
    public function updateSecurityAdvisoryStatus($id) {
        $data = json_decode(file_get_contents("php://input"));

        if (!$data || !isset($data->status)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            return;
        }

        $existing = $this->securityAdvisory->findOne($id);
        if (!$existing) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => '安全公告不存在']);
            return;
        }

        if ($this->securityAdvisory->updateStatus($id, $data->status)) {
            echo json_encode([
                'success' => true,
                'message' => $data->status === 'resolved' ? '漏洞已解除' : '状态已更新'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => '更新状态失败']);
        }
    }

    // DELETE /api/security/advisories/:id
    public function deleteSecurityAdvisory($id) {
        $existing = $this->securityAdvisory->findOne($id);
        if (!$existing) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => '安全公告不存在']);
            return;
        }

        if ($this->securityAdvisory->delete($id)) {
            echo json_encode(['success' => true, 'message' => '安全公告已删除']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => '删除失败']);
        }
    }

    // GET /api/security/match/:keyword
    public function matchSecurityAdvisories($keyword) {
        $advisories = $this->securityAdvisory->matchByKeyword(urldecode($keyword));
        
        foreach ($advisories as &$advisory) {
            if (!empty($advisory['alternative_resources'])) {
                $advisory['alternative_resources'] = json_decode($advisory['alternative_resources'], true);
            }
        }

        echo json_encode([
            'success' => true,
            'data' => $advisories
        ]);
    }

    private function getHighestSeverity($advisories) {
        $severityOrder = ['critical' => 4, 'high' => 3, 'medium' => 2, 'low' => 1];
        $highest = null;
        $highestLevel = 0;
        foreach ($advisories as $advisory) {
            $level = $severityOrder[$advisory['severity']] ?? 0;
            if ($level > $highestLevel) {
                $highestLevel = $level;
                $highest = $advisory['severity'];
            }
        }
        return $highest;
    }

    private function generateDemoData($query, $provider, $page) {
        $demoTorrents = [];
        $queryLower = strtolower($query);

        $vulnerableComponents = [
            'log4j' => [
                ['version' => '2.14.1', 'vulnerable' => true],
                ['version' => '2.17.1', 'vulnerable' => false],
                ['version' => '2.10.0', 'vulnerable' => true],
            ],
            'openssl' => [
                ['version' => '1.0.1f', 'vulnerable' => true],
                ['version' => '1.0.1g', 'vulnerable' => false],
                ['version' => '1.0.2k', 'vulnerable' => false],
            ],
            'struts2' => [
                ['version' => '2.3.32', 'vulnerable' => true],
                ['version' => '2.5.12', 'vulnerable' => false],
                ['version' => '2.5.10', 'vulnerable' => true],
            ],
        ];

        $matchedComponent = null;
        foreach ($vulnerableComponents as $comp => $versions) {
            if (strpos($queryLower, $comp) !== false) {
                $matchedComponent = $comp;
                break;
            }
        }

        if ($matchedComponent) {
            $versions = $vulnerableComponents[$matchedComponent];
            foreach ($versions as $index => $verInfo) {
                $randomString = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
                $item = [
                    'Name' => ucfirst($matchedComponent) . " " . $verInfo['version'] . " - Full Package [$provider]",
                    'Magnet' => "magnet:?xt=urn:btih:DEMO$randomString&dn=" . urlencode($matchedComponent . $verInfo['version']),
                    'Size' => rand(10, 500) . " MB",
                    'Seeders' => rand(50, 2000),
                    'Leechers' => rand(10, 500),
                    'Category' => 'Software',
                    'Version' => $verInfo['version'],
                    'Component' => $matchedComponent,
                    'Url' => "https://example.com/torrent/" . $matchedComponent . "-" . $verInfo['version'],
                    'DateUploaded' => rand(1, 365) . ' days ago'
                ];
                $demoTorrents[] = $item;
            }

            $extraCount = 2;
            for ($i = 0; $i < $extraCount; $i++) {
                $randomString = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
                $randVer = rand(1, 9) . "." . rand(0, 99) . "." . rand(0, 9);
                $item = [
                    'Name' => "$query Toolkit " . $randVer . " [$provider]",
                    'Magnet' => "magnet:?xt=urn:btih:DEMO$randomString&dn=" . urlencode($query),
                    'Size' => rand(10, 500) . " MB",
                    'Seeders' => rand(50, 2000),
                    'Leechers' => rand(10, 500),
                    'Category' => 'Software',
                    'Version' => $randVer,
                    'Url' => "https://example.com/torrent/" . strtolower(str_replace(' ', '-', $query)) . "-$i",
                    'DateUploaded' => rand(1, 30) . ' days ago'
                ];
                $demoTorrents[] = $item;
            }
        } else {
            $count = 5;
            for ($i = 0; $i < $count; $i++) {
                $randomString = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
                
                $item = [
                    'Name' => "$query Result " . ($i + 1) . " [$provider] [1080p]",
                    'Magnet' => "magnet:?xt=urn:btih:DEMO$randomString&dn=" . urlencode($query),
                    'Size' => rand(1, 20) . "." . rand(0, 99) . " GB",
                    'Seeders' => rand(50, 2000),
                    'Leechers' => rand(10, 500),
                    'Category' => 'Movies',
                    'Url' => "https://example.com/torrent/" . strtolower(str_replace(' ', '-', $query)) . "-$i",
                    'DateUploaded' => rand(1, 30) . ' days ago'
                ];
                $demoTorrents[] = $item;
            }
        }

        return $demoTorrents;
    }
}
