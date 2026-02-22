import * as THREE from 'three';
import { GLTFLoader }    from 'three/addons/loaders/GLTFLoader.js';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { DRACOLoader }   from 'three/addons/loaders/DRACOLoader.js';

// ── Configuration ────────────────────────────────────────────
const config = {
  modelPath:                  'models/house_optimized.glb',
  cameraFOV:                  75,
  cameraNear:                 0.1,
  cameraFar:                  1000,
  ambientLightIntensity:      0.6,
  directionalLightIntensity:  0.8,
};

// ── Edit hotspot positions to match your scan ─────────────────
const hotspotData = [
  { position: new THREE.Vector3(2,  2,  0),  title: 'Room 1',   content: 'Edit label and position in js/app.js' },
  { position: new THREE.Vector3(-3, 2,  2),  title: 'Room 2',   content: 'Edit label and position in js/app.js' },
  { position: new THREE.Vector3(0,  3, -4),  title: 'Room 3',   content: 'Edit label and position in js/app.js' },
];

// ── Globals ───────────────────────────────────────────────────
let scene, camera, renderer, controls, model, floorPlan;
let hotspots     = [];
const raycaster  = new THREE.Raycaster();
const mouse      = new THREE.Vector2();
let walkMode     = false;
const walkSpeed  = 0.15;
const keysDown   = new Set();
let measureMode  = false;
let measurePts   = [];
let minimapCamera, minimapRenderer;

init();
animate();

function init() {
  scene = new THREE.Scene();
  scene.background = new THREE.Color(0x87CEEB);
  scene.fog = new THREE.Fog(0x87CEEB, 50, 200);

  camera = new THREE.PerspectiveCamera(config.cameraFOV, innerWidth / innerHeight, config.cameraNear, config.cameraFar);
  camera.position.set(0, 5, 10);

  renderer = new THREE.WebGLRenderer({ antialias: true, preserveDrawingBuffer: true });
  renderer.setSize(innerWidth, innerHeight);
  renderer.setPixelRatio(devicePixelRatio);
  renderer.shadowMap.enabled = true;
  renderer.shadowMap.type = THREE.PCFSoftShadowMap;
  document.getElementById('canvas-container').appendChild(renderer.domElement);

  controls = new OrbitControls(camera, renderer.domElement);
  controls.enableDamping = true;
  controls.dampingFactor = 0.05;
  controls.minDistance   = 1;
  controls.maxDistance   = 60;
  controls.maxPolarAngle = Math.PI / 2.1;

  addLights();
  loadModel();
  setupMinimap();
  setupEvents();
  scene.add(new THREE.GridHelper(100, 100, 0x444444, 0x222222));
}

function addLights() {
  scene.add(new THREE.AmbientLight(0xffffff, config.ambientLightIntensity));
  const sun = new THREE.DirectionalLight(0xffffff, config.directionalLightIntensity);
  sun.position.set(10, 20, 10);
  sun.castShadow = true;
  sun.shadow.camera.left = sun.shadow.camera.bottom = -20;
  sun.shadow.camera.right = sun.shadow.camera.top = 20;
  sun.shadow.mapSize.set(2048, 2048);
  scene.add(sun);
  const fill = new THREE.DirectionalLight(0xffffff, 0.3);
  fill.position.set(-10, 10, -10);
  scene.add(fill);
}

function loadModel() {
  const draco = new DRACOLoader();
  draco.setDecoderPath('https://cdn.jsdelivr.net/npm/three@0.169.0/examples/jsm/libs/draco/');
  const loader = new GLTFLoader();
  loader.setDRACOLoader(draco);

  loader.load(
    config.modelPath,
    (gltf) => {
      model = gltf.scene;
      const box = new THREE.Box3().setFromObject(model);
      model.position.sub(box.getCenter(new THREE.Vector3()));
      const size = box.getSize(new THREE.Vector3());
      model.scale.setScalar(10 / Math.max(size.x, size.y, size.z));
      model.traverse(c => { if (c.isMesh) { c.castShadow = c.receiveShadow = true; } });
      scene.add(model);
      addHotspots();
      document.getElementById('loading').style.display = 'none';
    },
    (xhr) => {
      if (xhr.total) {
        document.getElementById('progress-fill').style.width = Math.round((xhr.loaded / xhr.total) * 100) + '%';
      }
    },
    (err) => {
      console.error(err);
      document.getElementById('loading').innerHTML =
        '<p style="color:#f55;padding:20px;text-align:center">Failed to load model.<br>Check console (F12).<br><br>' +
        'Make sure you are serving this folder from a local server,<br>not opening index.html directly as a file.</p>';
    }
  );
}

function addHotspots() {
  hotspotData.forEach((data) => {
    const geo  = new THREE.SphereGeometry(0.2, 16, 16);
    const mat  = new THREE.MeshBasicMaterial({ color: 0x4CAF50, transparent: true, opacity: 0.85 });
    const mesh = new THREE.Mesh(geo, mat);
    mesh.position.copy(data.position);
    const ring = new THREE.Mesh(
      new THREE.RingGeometry(0.3, 0.35, 32),
      new THREE.MeshBasicMaterial({ color: 0x4CAF50, side: THREE.DoubleSide, transparent: true, opacity: 0.5 })
    );
    ring.rotation.x = -Math.PI / 2;
    mesh.add(ring);
    mesh.userData = { title: data.title, content: data.content, isHotspot: true };
    hotspots.push(mesh);
    scene.add(mesh);
  });
}

function setupMinimap() {
  const canvas = document.getElementById('minimap-canvas');
  minimapCamera = new THREE.OrthographicCamera(-10, 10, 10, -10, 0.1, 100);
  minimapCamera.position.set(0, 20, 0);
  minimapCamera.lookAt(0, 0, 0);
  minimapRenderer = new THREE.WebGLRenderer({ canvas, alpha: true });
  minimapRenderer.setSize(200, 200);
  minimapRenderer.setClearColor(0x000000, 0.3);
}

function setupEvents() {
  window.addEventListener('resize',    onResize);
  window.addEventListener('mousemove', onMouseMove);
  window.addEventListener('click',     onMouseClick);
  window.addEventListener('keydown',   e => keysDown.add(e.key));
  window.addEventListener('keyup',     e => keysDown.delete(e.key));

  document.getElementById('reset-view').addEventListener('click', () =>
    animateCameraTo(new THREE.Vector3(0, 5, 10), new THREE.Vector3(0, 0, 0)));

  document.getElementById('toggle-minimap').addEventListener('click', () => {
    const mm = document.getElementById('minimap');
    mm.style.display = mm.style.display === 'none' ? 'block' : 'none';
  });

  document.getElementById('toggle-walkmode').addEventListener('click', () => {
    walkMode = !walkMode;
    controls.enabled = !walkMode;
    const btn = document.getElementById('toggle-walkmode');
    btn.textContent = 'Walk Mode: ' + (walkMode ? 'ON' : 'OFF');
    btn.classList.toggle('active', walkMode);
  });

  document.getElementById('toggle-floorplan').addEventListener('click', () => {
    if (!floorPlan) floorPlan = createFloorPlan();
    if (model) model.visible = !model.visible;
    floorPlan.visible = !floorPlan.visible;
  });

  document.getElementById('measure-mode').addEventListener('click', () => {
    measureMode = !measureMode;
    measurePts  = [];
    const btn = document.getElementById('measure-mode');
    btn.textContent = measureMode ? 'Exit Measure Mode' : 'Measure Distance';
    btn.classList.toggle('active', measureMode);
    document.getElementById('measurement-display').textContent =
      measureMode ? 'Click two points on the model' : '';
  });

  document.getElementById('screenshot').addEventListener('click', () => {
    renderer.render(scene, camera);
    const a = document.createElement('a');
    a.download = 'house-tour-screenshot.png';
    a.href = renderer.domElement.toDataURL('image/png');
    a.click();
  });
}

function createFloorPlan() {
  const group = new THREE.Group();
  const points = [
    new THREE.Vector3(-5, 0.1, -5), new THREE.Vector3(5, 0.1, -5),
    new THREE.Vector3(5, 0.1,  5),  new THREE.Vector3(-5, 0.1, 5),
    new THREE.Vector3(-5, 0.1, -5),
  ];
  group.add(new THREE.Line(
    new THREE.BufferGeometry().setFromPoints(points),
    new THREE.LineBasicMaterial({ color: 0x000000 })
  ));
  const floor = new THREE.Mesh(
    new THREE.PlaneGeometry(10, 10),
    new THREE.MeshBasicMaterial({ color: 0xffffff, transparent: true, opacity: 0.8, side: THREE.DoubleSide })
  );
  floor.rotation.x = -Math.PI / 2;
  floor.position.y = 0.05;
  group.add(floor);
  group.visible = false;
  scene.add(group);
  return group;
}

function onResize() {
  camera.aspect = innerWidth / innerHeight;
  camera.updateProjectionMatrix();
  renderer.setSize(innerWidth, innerHeight);
}

function onMouseMove(e) {
  mouse.x =  (e.clientX / innerWidth)  * 2 - 1;
  mouse.y = -(e.clientY / innerHeight) * 2 + 1;
  raycaster.setFromCamera(mouse, camera);
  const hits = raycaster.intersectObjects(hotspots, true);
  const tt   = document.getElementById('tooltip');
  if (hits.length > 0 && hits[0].object.userData.isHotspot) {
    const d = hits[0].object.userData;
    tt.style.display = 'block';
    tt.style.left = (e.clientX + 15) + 'px';
    tt.style.top  = (e.clientY + 15) + 'px';
    document.getElementById('tooltip-title').textContent   = d.title;
    document.getElementById('tooltip-content').textContent = d.content;
    document.body.style.cursor = 'pointer';
  } else {
    tt.style.display = 'none';
    document.body.style.cursor = 'default';
  }
}

function onMouseClick(e) {
  raycaster.setFromCamera(mouse, camera);
  if (measureMode && model) {
    const hits = raycaster.intersectObject(model, true);
    if (hits.length > 0) {
      measurePts.push(hits[0].point.clone());
      if (measurePts.length === 2) {
        const dist = measurePts[0].distanceTo(measurePts[1]);
        document.getElementById('measurement-display').textContent = 'Distance: ' + dist.toFixed(2) + ' m';
        measurePts = [];
      }
    }
    return;
  }
  const hits = raycaster.intersectObjects(hotspots, true);
  if (hits.length > 0 && hits[0].object.userData.isHotspot) {
    animateCameraTo(hits[0].object.position);
  }
}

function animateCameraTo(target, lookAt = null) {
  const start = camera.position.clone();
  const end   = target.clone();
  end.y += 2; end.z += 3;
  const t0 = Date.now(), dur = 1500;
  (function tick() {
    const p = Math.min((Date.now() - t0) / dur, 1);
    const e = p < 0.5 ? 2*p*p : 1 - Math.pow(-2*p+2, 2)/2;
    camera.position.lerpVectors(start, end, e);
    controls.target.copy(lookAt ?? target);
    controls.update();
    if (p < 1) requestAnimationFrame(tick);
  })();
}

function processWalk() {
  if (!walkMode) return;
  const dir = new THREE.Vector3();
  camera.getWorldDirection(dir); dir.y = 0; dir.normalize();
  const right = new THREE.Vector3().crossVectors(dir, new THREE.Vector3(0, 1, 0));
  if (keysDown.has('w') || keysDown.has('ArrowUp'))    camera.position.addScaledVector(dir,   walkSpeed);
  if (keysDown.has('s') || keysDown.has('ArrowDown'))  camera.position.addScaledVector(dir,  -walkSpeed);
  if (keysDown.has('a') || keysDown.has('ArrowLeft'))  camera.position.addScaledVector(right,-walkSpeed);
  if (keysDown.has('d') || keysDown.has('ArrowRight')) camera.position.addScaledVector(right, walkSpeed);
}

function animate() {
  requestAnimationFrame(animate);
  processWalk();
  controls.update();
  hotspots.forEach((h, i) => {
    const ring = h.children[0];
    if (ring) {
      ring.rotation.z += 0.01;
      ring.scale.x = ring.scale.y = 1 + Math.sin(Date.now() * 0.002 + i) * 0.1;
    }
  });
  if (minimapRenderer && minimapCamera) {
    minimapRenderer.render(scene, minimapCamera);
    const ind = document.getElementById('minimap-player');
    if (ind) {
      ind.style.left = Math.max(0, Math.min(100, (camera.position.x / 20) * 100 + 50)) + '%';
      ind.style.top  = Math.max(0, Math.min(100, (camera.position.z / 20) * 100 + 50)) + '%';
    }
  }
  renderer.render(scene, camera);
}