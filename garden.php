<?php
require_once 'env.php';
loadEnv();
require_once 'config/constants.php';
session_start();
require_once 'classes/GardenManager.php';
// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}

$gardenManager = new GardenManager();
$userId = $_SESSION['user_id'];

// Get user's garden
try {
    $plants = $gardenManager->getUserGarden($userId);
} catch (Exception $e) {
    $error = $e->getMessage();
    $plants = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Virtual Garden - BossGPT</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <!-- <link rel="stylesheet" href="assets/css/custom.css"> -->
    <link rel="stylesheet" href="assets/css/optimize.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            overflow: hidden;
        }

        canvas {
            display: block;
        }

        #gardenCanvas {
            opacity: 0;
            transition: opacity 1s ease;
        }

        .garden-container {
            position: relative;
            width: 100%;
            height: 100vh;
        }

        .garden-overlay {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 10;
            background: rgba(0, 0, 0, 0.5);
            padding: 15px;
            border-radius: 10px;
            color: white;
        }

        .achievement-badge {
            display: inline-block;
            margin: 5px;
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            font-size: 0.9rem;
        }

        .garden-stats {
            margin-top: 20px;
        }

        .back-button {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 10;
        }

        /* Loader spinner styles */
        .loader-spinner {
            width: 40px;
    height: 40px;
    border: 5px solid rgb(255 255 255 / 57%);
    border-radius: 50%;
    border-top-color: #339c29;
    animation: spin 1.2s ease-in-out infinite;
            margin: 0 auto 15px auto;
        }

        body.loaded #gardenCanvas {
            opacity: 1;
        }

        #loadingOverlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(20px);
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    .garden-overlay-content{
        text-align: center;
    padding: 4rem 4rem;
    background: rgba(0, 0, 0, 0.35);
    border-radius: 12px;
    color: white;
    }
    @media (min-width:576px) {
        .garden-overlay-content {
            width: 650px;
        }
    }
    .tree_image{
        width:100px; height:100px;
    }
    
    </style>
</head>

<body class="dark-mode">
    <div class="garden-container">
        <!-- Add loading overlay -->
        <div id="loadingOverlay"
            class="position-fixed top-0 start-0 w-100 h-100 d-flex flex-column justify-content-center align-items-center"
            >
<div class="d-flex flex-column justify-content-center align-items-center garden-overlay-content">
<div class="text-center mb-4">
                <img src="assets/images/garden/seed.png" alt="Garden Seed" class="tree_image" class="mb-3 animate__animated animate__pulse animate__infinite">
<img src="assets/images/garden/treelv2.png" alt="Garden Seed" class="tree_image" class="mb-3 animate__animated animate__pulse animate__infinite">
<img src="assets/images/garden/treelv6.png" alt="Garden Seed" class="tree_image" class="mb-3 animate__animated animate__pulse animate__infinite">

                <h2 class="text-white">Growing Your Garden</h2>
            </div>
            <div class="loader-spinner"></div>
            <div id="loadingText" class="text-white">Loading assets (0%)</div>
</div>
        </div>

        <canvas id="gardenCanvas"></canvas>

        <div class="garden-overlay">
            <h2>Your Virtual Garden</h2>
            <div class="garden-stats">
                <p>Total Plants: <span id="totalPlants"><?= count($plants) ?></span></p>
                <p>Completed Tasks (Lush Trees): <span
                        id="completedTasks"><?= count(array_filter($plants, function ($p) {
                            return $p['stage'] == 'tree'; })) ?></span>
                </p>
                <p>Growing Plants: <span
                        id="growingPlants"><?= count(array_filter($plants, function ($p) {
                            return $p['stage'] == 'growing'; })) ?></span>
                </p>
                <p>Seeds Planted: <span
                        id="seedsPlanted"><?= count(array_filter($plants, function ($p) {
                            return $p['stage'] == 'sprout'; })) ?></span>
                </p>
            </div>

            <?php if (count($plants) > 0): ?>
                <div class="achievements mt-4">
                    <h4>Your Achievements</h4>
                    <?php if (count($plants) >= 5): ?>
                        <div class="achievement-badge">🌱 Garden Starter</div>
                    <?php endif; ?>

                    <?php if (count(array_filter($plants, function ($p) {
                        return $p['stage'] == 'lush_tree'; })) >= 3): ?>
                        <div class="achievement-badge">🌳 Forest Creator</div>
                    <?php endif; ?>

                    <?php if (count(array_filter($plants, function ($p) {
                        return $p['stage'] == 'lush_tree' && $p['size'] == 'large'; })) >= 1): ?>
                        <div class="achievement-badge">🌲 Project Completer</div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <a href="index.php" class="btn back-button btn-main-primary">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <script type="module">
        import * as THREE from 'https://unpkg.com/three@0.150.0/build/three.module.js';

        // Get garden data passed from PHP
        const gardenData = <?= json_encode($plants) ?>;

        // --- LOADING MANAGER to track asset loading ---
        const loadingManager = new THREE.LoadingManager();
        const loadingOverlay = document.getElementById('loadingOverlay');
        const loadingText = document.getElementById('loadingText');
        const gardenCanvas = document.getElementById('gardenCanvas');

        // Check if canvas exists - if not, use a simplified loading approach
        const canvasExists = document.getElementById('gardenCanvas') !== null;

        // Manual progress simulation if canvas doesn't exist
        if (!canvasExists) {
            let progress = 0;
            const interval = setInterval(() => {
                progress += 5;
                loadingText.textContent = `Loading assets (${progress}%)`;

                if (progress >= 100) {
                    clearInterval(interval);
                    // Hide the overlay
                    setTimeout(() => {
                        loadingOverlay.style.transition = 'opacity 1s ease';
                        loadingOverlay.style.opacity = '0';
                        setTimeout(() => {
                            loadingOverlay.style.display = 'none';
                            document.body.style.overflow = 'auto';
                        }, 1000);
                    }, 500);
                }
            }, 100);
        }

        // Update loading progress
        loadingManager.onProgress = function (url, itemsLoaded, itemsTotal) {
            const progress = Math.round((itemsLoaded / itemsTotal) * 100);
            loadingText.textContent = `Loading assets (${progress}%)`;
        };

        // Hide loading overlay when everything is loaded
        loadingManager.onLoad = function () {
            // Hide the overlay completely once loaded
            setTimeout(() => {
                loadingOverlay.style.transition = 'opacity 1s ease';
                loadingOverlay.style.opacity = '0';
                setTimeout(() => {
                    loadingOverlay.style.display = 'none';
                    document.body.style.overflow = 'auto'; // Restore scrolling
                }, 1000);
                gardenCanvas.style.opacity = '1';
            }, 800);
        };

        // --- RENDERER ---
        const canvas = document.getElementById('gardenCanvas');
        const renderer = new THREE.WebGLRenderer({
            canvas,
            antialias: true,
            alpha: true,
            preserveDrawingBuffer: true
        });
        renderer.setSize(window.innerWidth, window.innerHeight);
        renderer.setPixelRatio(window.devicePixelRatio);

        // --- SCENE ---
        const scene = new THREE.Scene();
        scene.background = new THREE.Color(0x51a387);
        // --- CAMERA (Orthographic for isometric) ---
        const width = window.innerWidth;
        const height = window.innerHeight;
        const aspect = width / height;
        const viewSize = 50;

        const camera = new THREE.OrthographicCamera(
            -viewSize * aspect,
            viewSize * aspect,
            viewSize,
            -viewSize,
            1,
            1000
        );
        const isoAngle = Math.PI / 6;
        camera.position.set(100 * Math.cos(isoAngle), 70, 100 * Math.sin(isoAngle));
        camera.lookAt(0, 0, 0);

        // --- LIGHTING ---
        scene.add(new THREE.AmbientLight(0xffffff, 0.7));
        const directional = new THREE.DirectionalLight(0xffffff, 0.4);
        directional.position.set(5, 10, 5);
        scene.add(directional);

        // --- TEXTURES ---
        const loader = new THREE.TextureLoader(loadingManager);

        const grassTexture = loader.load('assets/images/garden/grass_top.png');
        grassTexture.magFilter = THREE.NearestFilter;
        grassTexture.minFilter = THREE.NearestFilter;
        grassTexture.center.set(0.5, 0.5);
        grassTexture.rotation = Math.PI / 4;

        const sideA = loader.load('assets/images/garden/sideA.png');
        const sideB = loader.load('assets/images/garden/sideB.png');

        [sideA, sideB].forEach(tex => {
            tex.wrapS = THREE.ClampToEdgeWrapping;
            tex.wrapT = THREE.ClampToEdgeWrapping;
            tex.magFilter = THREE.LinearFilter;
            tex.minFilter = THREE.LinearFilter;
            tex.generateMipmaps = false;
        });

        // --- MATERIALS ---
        const topMat = new THREE.MeshLambertMaterial({
            map: grassTexture,
            transparent: true,
            alphaTest: 0.1,
            side: THREE.DoubleSide
        });

        const realisticSideA = loader.load('assets/images/garden/sideA.png');
        const realisticSideB = loader.load('assets/images/garden/sideB.png');

        [realisticSideA, realisticSideB].forEach(tex => {
            tex.wrapS = THREE.RepeatWrapping;
            tex.wrapT = THREE.RepeatWrapping;
            tex.magFilter = THREE.LinearFilter;
            tex.minFilter = THREE.LinearMipMapLinearFilter;
        });

        // Load all possible plant textures
        const plantTextures = {};
        const plantTypes = [
            'seed', 'flower', 'treelv2', 'treelv3', 'treelv4',
            'treelv5', 'treelv6', 'treelv7', 'treelv8', 'treebig',
            'dead', 'lush', 'treedead','flower1','flower3'
        ];

        plantTypes.forEach(type => {
            const texture = loader.load(`assets/images/garden/${type}.png`);
            texture.magFilter = THREE.LinearFilter;
            texture.minFilter = THREE.LinearMipMapLinearFilter;
            plantTextures[type] = texture;
        });

        // --- SIDES MATERIALS ---
        const sideAMat = new THREE.MeshLambertMaterial({
            map: realisticSideA,
            transparent: true,
            alphaTest: 0.5
        });

        const sideBMat = new THREE.MeshLambertMaterial({
            map: realisticSideB,
            transparent: true,
            alphaTest: 0.5
        });

        // --- TILE GEOMETRY ---
        const tileWidth = 10;
        const tileDepth = 10;
        const tileHeight = 6;
        const tileSpacing = 7;

        // --- TILE GRID ---
        const gridCount = 10;
        const offset = (gridCount * tileSpacing) / 2;

        for (let row = 0; row < gridCount; row++) {
            for (let col = 0; col < gridCount; col++) {
                const xPos = col * tileSpacing - offset + tileSpacing / 2;
                const zPos = row * tileSpacing - offset + tileSpacing / 2;

                const isLeftEdge = col === 0;
                const isRightEdge = col === gridCount - 1;
                const isBackEdge = row === 0;
                const isFrontEdge = row === gridCount - 1;

                const tileGroup = new THREE.Group();

                // TOP tile (Grass diamond)
                const topGeometry = new THREE.PlaneGeometry(tileWidth, tileDepth);
                const topMesh = new THREE.Mesh(topGeometry, topMat);
                topMesh.rotation.x = -Math.PI / 2;
                topMesh.position.y = tileHeight / 2;
                tileGroup.add(topMesh);

                // RIGHT SIDE (sideBMat)
                if (isRightEdge) {
                    const rightSideGeo = new THREE.PlaneGeometry(6.35, 10);
                    const rightSideMesh = new THREE.Mesh(rightSideGeo, sideBMat);
                    rightSideMesh.position.set(-tileWidth / 2 + 7.3, -1.35, -0.8);
                    rightSideMesh.rotation.y = 0.85;
                    tileGroup.add(rightSideMesh);
                }

                // FRONT SIDE (sideAMat)
                if (isFrontEdge) {
                    const frontSideGeo = new THREE.PlaneGeometry(4.1, 10);
                    const frontSideMesh = new THREE.Mesh(frontSideGeo, sideAMat);
                    frontSideMesh.position.set(0, -0.25, 3.30);
                    frontSideMesh.rotation.y = 0.6;
                    tileGroup.add(frontSideMesh);
                }

                // BACK SIDE (sideBMat)
                if (isBackEdge) {
                    const backSideGeo = new THREE.PlaneGeometry(tileWidth, tileHeight);
                    const backSideMesh = new THREE.Mesh(backSideGeo, sideBMat);
                    backSideMesh.position.set(0, 0, -tileDepth / 2);
                    backSideMesh.rotation.y = Math.PI;
                    tileGroup.add(backSideMesh);
                }

                // Adjust tileGroup scale slightly if needed
                tileGroup.scale.set(1.01, 1, 1.01);
                tileGroup.position.set(xPos, tileHeight / 2, zPos);
                scene.add(tileGroup);
            }
        }

        // --- CREATE PLANT MATERIALS ---
        const createPlantMesh = (plantType, stage) => {
            let textureKey = plantType;
            let texture = "";
            // Determine which texture to use based on stage and plantType
            if (stage === 'sprout') {
                texture = plantTextures['seed']
            } 
            else if (stage === 'growing') {
              
                    texture = plantTextures['flower3'];
            
            }else {
                texture = plantTextures[textureKey];
            }

            // Use the actual texture or default to seed if not found
          

            const plantMaterial = new THREE.MeshLambertMaterial({
                map: texture,
                transparent: true,
                alphaTest: 0.1,
                side: THREE.DoubleSide
            });

            // Size based on plant stage and type
            let width = 10;
            let height = 15;

            if (stage === 'sprout') {
                width = 9;
                height = 11;
            } else if (stage === 'growing') {
                width = 10;
                height = 16;
            } else if (stage === 'lush_tree') {
                width = 13;
                height = 19;
            }

            const geometry = new THREE.PlaneGeometry(width, height);
            return new THREE.Mesh(geometry, plantMaterial);
        };

        // --- PLACE PLANTS FROM USER GARDEN ---
        if (gardenData && gardenData.length > 0) {
            // Calculate how many plants we need to place
            const plantsCount = gardenData.length;

            // Determine how to arrange them in the grid
            const plantsPerRow = Math.min(Math.ceil(Math.sqrt(plantsCount)), gridCount);
            const rowsNeeded = Math.ceil(plantsCount / plantsPerRow);

            // Calculate starting position to center the garden
            const startRow = Math.floor((gridCount - rowsNeeded) / 2);
            const startCol = Math.floor((gridCount - plantsPerRow) / 2);

            gardenData.forEach((plant, index) => {
                const row = startRow + Math.floor(index / plantsPerRow);
                const col = startCol + (index % plantsPerRow);

                // Stay within grid bounds
                if (row < gridCount && col < gridCount) {
                    const xPos = col * tileSpacing - offset + tileSpacing / 2;
                    const zPos = row * tileSpacing - offset + tileSpacing / 2;

                    const plantMesh = createPlantMesh(plant.plant_type, plant.stage);

                    // Position on top of the tile
                    plantMesh.position.set(xPos, tileHeight + (plantMesh.geometry.parameters.height / 2) - 2, zPos);

                    // Rotate to match isometric view
                    plantMesh.rotation.y = Math.PI / 3.4;

                    scene.add(plantMesh);
                }
            });
        } 

        // --- RESIZE HANDLER ---
        window.addEventListener('resize', () => {
            const newWidth = window.innerWidth;
            const newHeight = window.innerHeight;
            const newAspect = newWidth / newHeight;

            camera.left = -viewSize * newAspect;
            camera.right = viewSize * newAspect;
            camera.updateProjectionMatrix();

            renderer.setSize(newWidth, newHeight);
        });

        // --- RENDER LOOP ---
        function animate() {
            requestAnimationFrame(animate);
            renderer.render(scene, camera);
        }

        animate();
    </script>
</body>

</html>