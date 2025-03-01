import * as THREE from 'three';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader';
import { DRACOLoader } from 'three/examples/jsm/loaders/DRACOLoader';
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls';

document.addEventListener("DOMContentLoaded", () => {
  const peopleSummary = document.getElementById("peopleSummary");
  let dragDropInitialized = false;

  function loadSummaryData() {
    const counts = {
      withPenis: { min: parseInt(getCache("manMin")) || 0, max: parseInt(getCache("manMax")) || 0 },
      withoutPenis: { min: parseInt(getCache("womanMin")) || 0, max: parseInt(getCache("womanMax")) || 0 }
    };

    // Get all assigned performers
    const assignedPerformers = new Set();
    ["withPenis", "withoutPenis"].forEach((type) => {
      for (let i = 0; i < counts[type].max; i++) {
        const assigned = getCache(`assignment_${type}_${i}`);
        if (assigned) {
          assignedPerformers.add(assigned.performerId);
        }
      }
    });

    // Filter out assigned performers from included performers
    const includedPerformers = (getCache("includedPerformerIds") || [])
      .map((id) => {
        const data = getCache(`performerName_${id}`);
        return data ? { id, name: data[0], gender: data[1] } : null;
      })
      .filter((performer) => performer && !assignedPerformers.has(performer.id));

    const excludedPerformers = (getCache("excludedPerformerIds") || [])
      .map((id) => {
        const data = getCache(`performerName_${id}`);
        return data ? { id, name: data[0], gender: data[1] } : null;
      })
      .filter(Boolean);

    return { counts, includedPerformers, excludedPerformers };
  }

  function renderSummary() {
    const { counts, includedPerformers, excludedPerformers } = loadSummaryData();

    function renderPeopleGrid(count, type) {
      const imgSrc = type === "withPenis" ? "man_front.svg" : "woman_front.svg";
      const title = type === "withPenis" ? "People with penis" : "People without a penis";
      const range = `${counts[type].min}-${counts[type].max}`;

      if (counts[type].max === 0) return "";

      const images = Array(counts[type].max)
        .fill()
        .map((_, index) => {
          // Check if position has assigned performer
          const assigned = getCache(`assignment_${type}_${index}`);
          const assignedClass = assigned ? "opacity-20" : "";
          const dropzoneClass = assigned ? '' : 'cursor-pointer open-modal';
          const assignedContent = assigned
            ? `
                    <div class="text-center relative w-full h-full flex flex-col items-center justify-center">
                        <button class="absolute top-5 right-5 -translate-y-1/2 translate-x-1/2 rounded-full p-1 remove-performer z-50 text-red-700 hover:text-white/50 transform transition-colors duration-300 ease-in-out">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div class="font-medium text-TextWhite">${assigned.name}</div>
                        <div class="text-xs text-secondary mt-1">${assigned.gender}</div>
                    </div>
                `
            : "";

          return `
                    <div class="dropzone bg-darkPrimairy/30 p-4 rounded-lg transition-all duration-300 hover:bg-darkPrimairy/50 select-none h-[180px] flex items-center justify-center ${dropzoneClass}">
                        <div class="relative w-32 h-32 border-2 border-dashed border-secondary/50 rounded-lg flex items-center justify-center overflow-hidden">
                            <div class="absolute inset-0 bg-darkPrimairy/20 z-0"></div>
                            <img src="/assets/images/website_Images/${imgSrc}" 
                                 class="relative z-10 w-24 h-24 transition-all duration-300 group-hover:opacity-20 ${assignedClass}"
                                 data-position="${index}"
                                 data-type="${type}">
                            <div class="performer-placeholder absolute inset-0 z-20 flex items-center justify-center text-center p-2 bg-darkPrimairy/40">
                                ${assignedContent}
                            </div>
                        </div>
                    </div>
                `;
        })
        .join("");

      return `
                <div class="w-full mb-8">
                    <h3 class="text-lg font-semibold mb-4">${title} <span class="text-secondary">(${range})</span></h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                        ${images}
                    </div>
                </div>
            `;
    }

    // Render performers as draggable cards
    function renderPerformerCards(performers, type) {
      if (!performers.length) return "";

      return `
                <div class="mt-6 bg-darkPrimairy/20 p-4 rounded-lg">
                    <h3 class="text-lg font-semibold mb-3">${type} Performers:</h3>
                    <div class="performers-grid grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        ${performers
                          .map(
                            (p, index) => `
                            <div class="draggable bg-darkPrimairy/30 p-3 rounded-lg cursor-grab hover:shadow-lg 
                                       transition-all duration-300 active:scale-95 select-none touch-none"
                                 data-performer-id="${p.id}"
                                 data-performer-name="${p.name}"
                                 data-performer-gender="${p.gender}"
                                 data-grid-position="${index}"
                                 style="transform: translate(0px, 0px); grid-area: auto;">
                                <div class="flex items-center gap-3">
                                    <span class="font-medium">${p.name}</span>
                                    <span class="text-sm text-secondary">${p.gender}</span>
                                </div>
                            </div>
                        `
                          )
                          .join("")}
                    </div>
                </div>
            `;
    }

    peopleSummary.innerHTML = `
            <div class="space-y-6 w-full max-w-6xl p-6">
                ${renderPeopleGrid(counts.withPenis.max, "withPenis")}
                ${renderPeopleGrid(counts.withoutPenis.max, "withoutPenis")}
                ${renderPerformerCards(includedPerformers, "Required")}
            </div>
        `;

    // Only initialize drag and drop once
    if (!dragDropInitialized) {
      initializeDragAndDrop();
      dragDropInitialized = true;
    }
  }

  function formatGenderType(gender) {
    return gender.toLowerCase().split(" ").join("");
  }

  function checkValidDrop(type, performerGender) {
    const formattedGender = formatGenderType(performerGender);
    return (type === "withPenis" && formattedGender === "withpenis") || (type === "withoutPenis" && formattedGender === "withoutapenis");
  }

  function initializeDragAndDrop() {
    // Remove existing event listeners
    document.removeEventListener("click", handleRemovePerformer);

    // Add click handler for remove buttons
    document.addEventListener("click", handleRemovePerformer);

    interact(".draggable").draggable({
      inertia: true,
      modifiers: [
        interact.modifiers.restrict({
          restriction: ".performers-grid",
          endOnly: true
        })
      ],
      autoScroll: true,
      listeners: {
        start(event) {
          const element = event.target;
          element.classList.add("ring-2", "ring-secondary", "opacity-75", "z-50");
          // Store original position
          const rect = element.getBoundingClientRect();
          element.setAttribute("data-start-x", rect.left);
          element.setAttribute("data-start-y", rect.top);
        },
        move(event) {
          const { target } = event;
          const x = (parseFloat(target.getAttribute("data-x")) || 0) + event.dx;
          const y = (parseFloat(target.getAttribute("data-y")) || 0) + event.dy;
          target.style.transform = `translate(${x}px, ${y}px)`;
          target.setAttribute("data-x", x);
          target.setAttribute("data-y", y);
        },
        end(event) {
          const element = event.target;
          element.classList.remove("ring-2", "ring-secondary", "opacity-75", "z-50");

          // If not dropped in a dropzone, animate back to grid position
          if (!document.querySelector(".dropzone.can-drop")) {
            element.style.transition = "all 0.3s ease";
            element.style.transform = "translate(0px, 0px)";
            element.setAttribute("data-x", 0);
            element.setAttribute("data-y", 0);

            // Remove transition after animation
            setTimeout(() => {
              element.style.transition = "";
            }, 300);
          }
        }
      }
    });

    interact(".dropzone").dropzone({
        
      accept: ".draggable",
      overlap: 0.5,
      ondropactivate: function (event) {
        const dropzone = event.target;
        const draggable = event.relatedTarget;
        const type = dropzone.querySelector("img").dataset.type;
        const performerGender = draggable.dataset.performerGender;
        const performerGenderFormatted = performerGender.toLowerCase().split(" ").join("");
        const placeholder = dropzone.querySelector(".performer-placeholder");

        // Check if empty or has remove button (can be replaced)
        const isEmpty = !placeholder.innerHTML || placeholder.querySelector(".remove-performer");

        // Check gender match
        const isValidDrop = checkValidDrop(type, performerGender);

        if (isEmpty && isValidDrop) {
          dropzone.classList.add("can-drop");
        }
      },
      ondropdeactivate: function (event) {
        const dropzone = event.target;
        dropzone.classList.remove("can-drop", "bg-red-500/20", "bg-green-500/20");
        dropzone.classList.remove("active-dropzone");
      },
      ondragenter: function (event) {
        const dropzone = event.target;
        const draggable = event.relatedTarget;
        const type = dropzone.querySelector("img").dataset.type;
        const performerGender = draggable.dataset.performerGender;
        
        dropzone.classList.add("active-dropzone");

        const isValidDrop = checkValidDrop(type, performerGender);

        // First remove any existing feedback classes
        dropzone.classList.remove("bg-red-500/20", "bg-green-500/20");

        if (isValidDrop) {
          dropzone.classList.add("bg-green-500/20");
          const placeholder = dropzone.querySelector(".performer-placeholder");
          placeholder.classList.add("bg-darkPrimairy/40");
          const image = dropzone.querySelector("img");
          if (image) image.classList.add("opacity-20", "scale-95");
        } else {
          dropzone.classList.add("bg-red-500/20");
          dropzone.classList.remove("can-drop");
        }
      },
      ondragleave: function (event) {
        const dropzone = event.target;
        dropzone.classList.remove("active-dropzone", "bg-red-500/20", "bg-green-500/20");
        const placeholder = dropzone.querySelector(".performer-placeholder");
        placeholder.classList.remove("bg-darkPrimairy/40");
        const image = dropzone.querySelector("img");
        if (image) {
          image.classList.remove("opacity-20", "scale-95");
        }
      },
      ondrop: function (event) {
        const draggable = event.relatedTarget;
        const dropzone = event.target;
        const image = dropzone.querySelector("img");
        const type = image.dataset.type;
        console.log("Type:", type);
        const placeholder = dropzone.querySelector(".performer-placeholder");
        const position = image.dataset.position;

        // Get performer data and update assignment
        const performerId = draggable.dataset.performerId;
        const performerName = draggable.dataset.performerName;
        const performerGender = draggable.dataset.performerGender;
        const performerGenderFormatted = performerGender.toLowerCase().split(" ").join("");

        console.log("performerGender:", performerGenderFormatted );

        // Validate gender match
        const isValidDrop = checkValidDrop(type, performerGender);

        if (!isValidDrop) {
          console.log("Invalid drop");
          return; // Prevent invalid drops
        }

        // If there's an existing assignment, remove it first
        const existingAssignment = getCache(`assignment_${type}_${position}`);
        if (existingAssignment) {
          setCache(`assignment_${type}_${position}`, null);
        }

        // Update cache first
        setCache(`assignment_${type}_${position}`, {
          performerId,
          name: performerName,
          gender: performerGender
        });

        // Update DOM directly instead of re-rendering
        image.classList.add("opacity-20");
        placeholder.classList.add("bg-darkPrimairy/40");
        placeholder.innerHTML = `
                    <div class="text-center relative w-full h-full flex flex-col items-center justify-center">
                        <button class="absolute top-5 right-5 -translate-y-1/2 translate-x-1/2 rounded-full p-1 remove-performer z-50 text-red-700 hover:text-white/50 transform transition-colors duration-300 ease-in-out">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div class="font-medium text-TextWhite">${performerName}</div>
                        <div class="text-xs text-secondary mt-1">${performerGender}</div>
                    </div>
                `;

        // Remove the draggable element
        draggable.remove();
      }
    });

    // Add click handler for unassigned dropzones
    document.addEventListener('click', (e) => {
      const dropzone = e.target.closest('.dropzone.open-modal');
      if (!dropzone) return;

      const type = dropzone.querySelector('img').dataset.type;
      const position = dropzone.querySelector('img').dataset.position;
      
      // Don't open modal if dropzone is assigned
      if (getCache(`assignment_${type}_${position}`)) return;

      openAssignmentModal(type, position);
    });
  }

  // Update initThreeJsScene to accept an optional callback
  function initThreeJsScene(containerId, modelPath, onModelLoaded) {
    const container = document.getElementById(containerId);
    const width = container.clientWidth;
    const height = container.clientHeight;
  
    const scene = new THREE.Scene();
    scene.background = new THREE.Color(0x0d0d0d);
  
    // More precise model type check
    const isMaleModel = modelPath.endsWith('/male.glb') && !modelPath.endsWith('/female.glb');
  
    // Same camera setup for both models
    const camera = new THREE.PerspectiveCamera(25, width / height, 0.1, 1000);
    camera.position.set(0, 1.2, 5);
    camera.lookAt(0, 1.2, 0);
  
    // Setup renderer
    const renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setSize(width, height);
    // Fix for deprecated outputEncoding
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    container.appendChild(renderer.domElement);
  
    // Initialize OrbitControls for user spinning with fixed x and y
    const controls = new OrbitControls(camera, renderer.domElement);
    controls.enablePan = false;  // Disallow panning so x and y stay unchanged
    controls.enableZoom = true;  // Allow zoom if needed
    controls.minPolarAngle = Math.PI / 2;
    controls.maxPolarAngle = Math.PI / 2;
  
    // Improved lighting
    const ambientLight = new THREE.AmbientLight(0xffffff, 1);
    scene.add(ambientLight);
  
    const directionalLight = new THREE.DirectionalLight(0xffffff, 1);
    directionalLight.position.set(2, 2, 2);
    scene.add(directionalLight);
  
    // Initialize DRACO loader
    const dracoLoader = new DRACOLoader();
    dracoLoader.setDecoderPath('https://www.gstatic.com/draco/versioned/decoders/1.5.6/'); // Use CDN path
    dracoLoader.preload();
  
    // Create and configure GLTFLoader
    const loader = new GLTFLoader();
    loader.setDRACOLoader(dracoLoader);
    
    // Add loading indicator
    container.innerHTML = '<p class="text-center text-secondary">Loading 3D model...</p>';
  
    // Load model with error handling
    try {
        loader.load(
            modelPath,
            (gltf) => {
                container.innerHTML = ''; // Clear loading message
                container.appendChild(renderer.domElement);
                
                const model = gltf.scene;
                if (isMaleModel) {
                    model.scale.setScalar(1.5);
                    // Center the male model using its bounding box
                    const box = new THREE.Box3().setFromObject(model);
                    const center = box.getCenter(new THREE.Vector3());
                    model.position.sub(center);
                    // Optionally adjust vertical offset as needed
                    model.position.y += 0.1;
                    
                    // Adjust skin tone for male model to be more like the female model
                    model.traverse((child) => {
                        if (child.isMesh && child.material) {
                            // Blend current color with a lighter skin tint (e.g. 0xffe0bd)
                            child.material.color.lerp(new THREE.Color(0x8d5524), 0.5);
                            child.material.needsUpdate = true;
                        }
                    });
                } else {
                    model.scale.setScalar(1.0);
                    model.position.set(0, 0, 0); // Reset position first
                    // Center the model using bounding box
                    const box = new THREE.Box3().setFromObject(model);
                    const center = box.getCenter(new THREE.Vector3());
                    model.position.sub(center);
                    // Adjust final position
                    model.position.y += 0.1;
                }
                
                model.rotation.y = -Math.PI / 12;
                scene.add(model);
  
                // Update lighting for male model to brighten all areas
                if (isMaleModel) {
                    // Replace existing lights with stronger ones:
                    const backLight = new THREE.DirectionalLight(0xffffff, 1.0);
                    backLight.position.set(0, 0, -5);
                    scene.add(backLight);
                    
                    const fillLight = new THREE.DirectionalLight(0xffffff, 1.0);
                    fillLight.position.set(5, 5, 5);
                    scene.add(fillLight);
                    
                    // Add an overhead hemisphere light to further light the model
                    const hemiLight = new THREE.HemisphereLight(0xffffff, 0x444444, 0.75);
                    hemiLight.position.set(0, 20, 0);
                    scene.add(hemiLight);
                    
                    // ADDITIONAL CHANGE: Add a left side light to brighten the dark left area.
                    const leftLight = new THREE.DirectionalLight(0xffffff, 0.8);
                    leftLight.position.set(-5, 2, 0);
                    // Optionally disable shadows for performance
                    leftLight.castShadow = false;
                    scene.add(leftLight);
                }
                
                // Start animation loop after model is loaded
                function animate() {
                    requestAnimationFrame(animate);
                    controls.update();
                    renderer.render(scene, camera);
                }
                animate();
                if (onModelLoaded) onModelLoaded(model);
            },
            (xhr) => {
                const percent = (xhr.loaded / xhr.total * 100);
                container.innerHTML = `<p class="text-center text-secondary">Loading: ${Math.round(percent)}%</p>`;
            },
            (error) => {
                console.error('Error loading model:', error);
                container.innerHTML = '<p class="text-red-500 text-center">Error loading 3D model</p>';
            }
        );
    } catch (error) {
        console.error('Error initializing loader:', error);
        container.innerHTML = '<p class="text-red-500 text-center">Error initializing 3D viewer</p>';
    }
  
    // Handle window resize
    window.addEventListener('resize', () => {
        const newWidth = container.clientWidth;
        camera.aspect = newWidth / height;
        camera.updateProjectionMatrix();
        renderer.setSize(newWidth, height);
    });
  }

  // Modify openAssignmentModal to update model on customization change
  function openAssignmentModal(type, position) {
    const modelPath = type === 'withPenis' 
        ? '/assets/3dmodels/male.glb' 
        : '/assets/3dmodels/female.glb';
    
    // Updated modal HTML with extra customization options
    const modalHtml = `
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4 overflow-auto" id="assignmentModal">
            <div class="bg-darkPrimairy p-6 rounded-lg w-full max-w-6xl h-auto">
                <div class="flex justify-between items-center mb-6 border-b border-secondary pb-2">
                    <h3 class="text-xl font-semibold">Edit your own performer</h3>
                    <button id="closeModal" class="text-secondary hover:text-white transition duration-100 close-modal">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="flex flex-col md:flex-row gap-6">
                    <div class="flex flex-col items-center justify-center w-full md:w-[400px] h-[500px]">
                        <div id="modelViewer" class="w-full h-full bg-darkPrimairy/50 rounded-lg"></div>
                    </div>
                    <!-- Vertical separator line on medium screens and up -->
                    <div class="hidden md:block border-l border-secondary"></div>
                    <div class="flex-1">
                        <div id="customOptions" class="p-4 bg-darkPrimairy/30 rounded-lg">
                            <h4 class="text-xl font-semibold mb-4">3D Model Customization</h4>
                            <!-- New customization inputs -->
                            <div class="mb-4">
                                <label for="skinColor" class="block text-sm mb-1">Skin Color</label>
                                <input type="color" id="skinColor" value="#f1c27d">
                            </div>
                            <div class="mb-4">
                                <label for="height" class="block text-sm mb-1">Height (m)</label>
                                <input type="number" id="height" step="0.01" min="1" max="2.5" value="1.70">
                            </div>
                            <div class="mb-4">
                                <label for="hairColor" class="block text-sm mb-1">Hair Color</label>
                                <input type="color" id="hairColor" value="#3b2c29">
                            </div>
                        </div>
                    </div>
                </div>
                <!-- New modal content container for performer selection -->
                <div id="modalContent"></div>
                <!-- ...existing code if needed... -->
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);

    // Declare variable for the loaded custom model
    let customModel = null;
    // Initialize 3D scene with a callback to assign customModel
    requestAnimationFrame(() => {
      initThreeJsScene('modelViewer', modelPath, (model) => {
        customModel = model;
        attachCustomizationListeners();
      });
    });
    
    // Function: attach customization change listeners to inputs
    function attachCustomizationListeners() {
      const skinInput = document.getElementById('skinColor');
      const heightInput = document.getElementById('height');
      const hairInput = document.getElementById('hairColor');
  
      // Update model appearance based on the current customization values
      function updateModelCustomization() {
        if (!customModel) return;
        const newSkinColor = new THREE.Color(skinInput.value);
        const newHairColor = new THREE.Color(hairInput.value);
        const newHeight = parseFloat(heightInput.value) || 1.70; // in meters
  
        // Traverse model and update materials
        customModel.traverse((child) => {
          if (child.isMesh && child.material) {
            // Assume mesh named "hair" is hair material, others update skin tone
            if (child.name.toLowerCase().includes('hair')) {
              child.material.color = newHairColor;
            } else {
              child.material.color = newSkinColor;
            }
          }
        });
        // Adjust scale to mimic height change (assuming default height = 1.70m)
        const scaleFactor = newHeight / 1.70;
        customModel.scale.setScalar(scaleFactor);
      }
  
      // Add event listeners to update model on changes
      skinInput.addEventListener('input', updateModelCustomization);
      heightInput.addEventListener('input', updateModelCustomization);
      hairInput.addEventListener('input', updateModelCustomization);
    }
    
    const modal = document.getElementById('assignmentModal');
    modal.addEventListener('click', (e) => {
        if (e.target.closest('.close-modal') || e.target === modal) {
            modal.remove();
        }
    });

    // Populate modal content with available performers if modalContent exists
    const modalContent = document.getElementById('modalContent');
    if (modalContent) {
        modalContent.addEventListener('click', (e) => {
            const performerOption = e.target.closest('.performer-option');
            if (!performerOption) return;
    
            // Retrieve customization values
            const skinColor = document.getElementById('skinColor')?.value;
            const height = document.getElementById('height')?.value;
            const hairColor = document.getElementById('hairColor')?.value;
    
            const performerData = {
                performerId: performerOption.dataset.performerId,
                name: performerOption.dataset.performerName,
                gender: performerOption.dataset.performerGender,
                skinColor,  // new property
                height,     // new property
                hairColor   // new property
            };
    
            setCache(`assignment_${type}_${position}`, performerData);
            modal.remove();
            renderSummary();
        });
    }
    
  }

  // Separate function for remove handler
  function handleRemovePerformer(e) {
    if (e.target.closest(".remove-performer")) {
      const dropzone = e.target.closest(".dropzone");
      const image = dropzone.querySelector("img");
      const position = image.dataset.position;
      const type = image.dataset.type;

      // Get the performer data before removing from cache
      const assignment = getCache(`assignment_${type}_${position}`);
      if (!assignment) return;

      // Remove assignment from cache
      setCache(`assignment_${type}_${position}`, null);

      // Update DOM directly for the dropzone
      image.classList.remove("opacity-20");
      const placeholder = dropzone.querySelector(".performer-placeholder");
      placeholder.innerHTML = "";
      placeholder.classList.remove("bg-darkPrimairy/40");

      // Force a complete re-render to properly update the Required performers grid
      renderSummary();
    }
  }

  // Initial render
  renderSummary();

  // Debounce the storage event handler
  let storageTimeout;
  window.addEventListener("storage", (e) => {
    if (e.key?.startsWith("performer") || e.key?.includes("Min") || e.key?.includes("Max")) {
      clearTimeout(storageTimeout);
      storageTimeout = setTimeout(() => {
        renderSummary();
      }, 100);
    }
  });

  window.addEventListener("myCustomUpdate", () => {
    renderSummary(); // re-render on custom event
  });
});
