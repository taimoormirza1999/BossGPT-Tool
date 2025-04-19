<?php
require_once __DIR__ . '/Database.php';

class GardenManager
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get all plants in a user's garden
     * 
     * @param int $userId User ID
     * @return array Plants data
     */
    public function getUserGarden($userId)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT g.*, t.title as task_title, t.description as task_description, t.status as task_status, 
                        p.id as project_id, p.title as project_title  
                 FROM user_garden g
                 JOIN tasks t ON g.task_id = t.id
                 LEFT JOIN projects p ON t.project_id = p.id
                 WHERE g.user_id = ? 
                 ORDER BY g.updated_at DESC"
            );
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get User Garden Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a new plant in the garden when a task is created
     * 
     * @param int $taskId Task ID
     * @param int $userId User ID
     * @param string $size Task size (small, medium, large)
     * @return int Garden entry ID
     */
    public function plantSeed($taskId, $userId, $size = 'medium', $plantType = null)
    {
        try {
            $this->db->beginTransaction();

            // Determine plant type based on size
            if ($plantType === null) {
                $plantType = $this->getPlantTypeBySize($size);
            }
            
            $stmt = $this->db->prepare(
                "INSERT INTO user_garden (user_id, task_id, stage, plant_type, size) 
                VALUES (?, ?, 'sprout', ?, ?)"
            );
            $stmt->execute([$userId, $taskId, $plantType, $size]);
            $gardenId = $this->db->lastInsertId();
            
            $this->db->commit();
            return $gardenId;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Plant Seed Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update plant stage based on task status
     * 
     * @param int $taskId Task ID
     * @param string $taskStatus New task status
     * @return bool Success
     */
    public function updatePlantStage($taskId, $taskStatus)
    {
        try {
            $stage = $this->getStageByTaskStatus($taskStatus);
            
            $stmt = $this->db->prepare(
                "UPDATE user_garden 
                 SET stage = ?, updated_at = NOW() 
                 WHERE task_id = ?"
            );
            $stmt->execute([$stage, $taskId]);
            
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log("Update Plant Stage Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get appropriate plant stage based on task status
     * 
     * @param string $taskStatus Task status
     * @return string Plant stage
     */
    private function getStageByTaskStatus($taskStatus)
    {
        switch ($taskStatus) {
            case 'todo':
                return 'sprout';
            case 'in_progress':
                return 'growing';
            case 'done':
               return 'tree';
            default:
                return 'sprout';
        }
    }

    /**
     * Determine plant type based on task size
     * 
     * @param string $size Task size
     * @return string Plant type
     */
    private function getPlantTypeBySize($size)
    {
        switch ($size) {
            case 'small':
                // For small tasks - choose a flower type
                $flowerTypes = ['flower', 'treelv5', 'treelv6'];
                return $flowerTypes[array_rand($flowerTypes)];
            
            case 'medium':
                // For medium tasks - choose a small tree type
                $mediumTreeTypes = ['treelv2', 'treelv3', 'treelv7', 'treelv8'];
                return $mediumTreeTypes[array_rand($mediumTreeTypes)];
            
            case 'large':
                // For large tasks - choose a large tree type
                $largeTreeTypes = ['treebig', 'treelv4', 'lush'];
                return $largeTreeTypes[array_rand($largeTreeTypes)];
            
            default:
                return 'treelv3'; // Default to a medium tree
        }
    }

    /**
     * Get asset filename for a plant based on stage and type
     * 
     * @param string $stage Plant growth stage
     * @param string $plantType Plant type
     * @return string Asset filename
     */
    public function getPlantAsset($stage, $plantType)
    {
        $basePath = 'assets/images/garden/';

        // Handle each stage
        switch ($stage) {
            case 'dead':
                return $basePath . 'dead.png';
            
            case 'sprout':
                return $basePath . 'seed.png';
            
            case 'growing':
                return $basePath . 'growing.png';
            
            case 'tree':
                // For tree stage, use the specific plant type image
                if ($plantType) {
                    return $basePath . $plantType . '.png';
                }
                // Fallback to default tree image if no plant type
                return $basePath . 'treelv3.png';
            
            default:
                // Default to seed if stage is unknown
                return $basePath . 'seed.png';
        }
    }
} 