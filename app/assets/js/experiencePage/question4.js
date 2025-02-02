document.addEventListener('DOMContentLoaded', () => {
    const peopleSummary = document.getElementById('peopleSummary');
    
    function loadSummaryData() {
        // Get counts from cache
        const counts = {
            withPenis: { min: parseInt(getCache('manMin')) || 0, max: parseInt(getCache('manMax')) || 0 },
            withoutPenis: { min: parseInt(getCache('womanMin')) || 0, max: parseInt(getCache('womanMax')) || 0 }
        };

        // Get performers from cache
        const includedPerformers = (getCache('includedPerformerIds') || [])
            .map(id => {
                const data = getCache(`performerName_${id}`);
                return data ? { id, name: data[0], gender: data[1] } : null;
            })
            .filter(Boolean);
        
        const excludedPerformers = (getCache('excludedPerformerIds') || [])
            .map(id => {
                const data = getCache(`performerName_${id}`);
                return data ? { id, name: data[0], gender: data[1] } : null;
            })
            .filter(Boolean);

        return { counts, includedPerformers, excludedPerformers };
    }

    function renderSummary() {
        const { counts, includedPerformers, excludedPerformers } = loadSummaryData();

        function renderPeopleGrid(count, type) {
            const imgSrc = type === 'withPenis' ? 'man_front.svg' : 'woman_front.svg';
            const title = type === 'withPenis' ? 'People with penis' : 'People without penis';
            const range = `${counts[type].min}-${counts[type].max}`;
            
            if (counts[type].max === 0) return '';

            const images = Array(counts[type].max).fill().map((_, index) => {
                // Check if position has assigned performer
                const assigned = getCache(`assignment_${type}_${index}`);
                const assignedClass = assigned ? 'opacity-20' : '';
                const assignedContent = assigned ? `
                    <div class="text-center">
                        <div class="font-medium text-TextWhite">${assigned.name}</div>
                        <div class="text-xs text-secondary mt-1">${assigned.gender}</div>
                    </div>
                ` : '';

                return `
                    <div class="dropzone bg-darkPrimairy/30 p-4 rounded-lg transition-all duration-300 hover:bg-darkPrimairy/50 select-none h-[180px] flex items-center justify-center">
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
            }).join('');

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
            if (!performers.length) return '';
            
            return `
                <div class="mt-6 bg-darkPrimairy/20 p-4 rounded-lg">
                    <h3 class="text-lg font-semibold mb-3">${type} Performers:</h3>
                    <div class="performers-grid grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        ${performers.map((p, index) => `
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
                        `).join('')}
                    </div>
                </div>
            `;
        }

        peopleSummary.innerHTML = `
            <div class="space-y-6 w-full max-w-6xl p-6">
                ${renderPeopleGrid(counts.withPenis.max, 'withPenis')}
                ${renderPeopleGrid(counts.withoutPenis.max, 'withoutPenis')}
                ${renderPerformerCards(includedPerformers, 'Required')}
            </div>
        `;

        // Initialize drag and drop
        initializeDragAndDrop();
    }

    function initializeDragAndDrop() {
        interact('.draggable').draggable({
            inertia: true,
            modifiers: [
                interact.modifiers.restrict({
                    restriction: '.performers-grid',
                    endOnly: true
                })
            ],
            autoScroll: true,
            listeners: {
                start(event) {
                    const element = event.target;
                    element.classList.add('ring-2', 'ring-secondary', 'opacity-75', 'z-50');
                    // Store original position
                    const rect = element.getBoundingClientRect();
                    element.setAttribute('data-start-x', rect.left);
                    element.setAttribute('data-start-y', rect.top);
                },
                move(event) {
                    const { target } = event;
                    const x = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx;
                    const y = (parseFloat(target.getAttribute('data-y')) || 0) + event.dy;
                    target.style.transform = `translate(${x}px, ${y}px)`;
                    target.setAttribute('data-x', x);
                    target.setAttribute('data-y', y);
                },
                end(event) {
                    const element = event.target;
                    element.classList.remove('ring-2', 'ring-secondary', 'opacity-75', 'z-50');
                    
                    // If not dropped in a dropzone, animate back to grid position
                    if (!document.querySelector('.dropzone.can-drop')) {
                        element.style.transition = 'all 0.3s ease';
                        element.style.transform = 'translate(0px, 0px)';
                        element.setAttribute('data-x', 0);
                        element.setAttribute('data-y', 0);
                        
                        // Remove transition after animation
                        setTimeout(() => {
                            element.style.transition = '';
                        }, 300);
                    }
                }
            }
        });

        // Update dropzone handling
        interact('.dropzone').dropzone({
            accept: '.draggable',
            overlap: 0.5,
            ondropactivate: function (event) {
                event.target.classList.add('can-drop');
            },
            ondropdeactivate: function (event) {
                event.target.classList.remove('can-drop');
            },
            ondragenter: function (event) {
                const dropzone = event.target;
                const placeholder = dropzone.querySelector('.performer-placeholder');
                placeholder.classList.add('bg-darkPrimairy/40');
                const image = dropzone.querySelector('img');
                if (image) image.classList.add('opacity-20', 'scale-95');
            },
            ondragleave: function (event) {
                const dropzone = event.target;
                const placeholder = dropzone.querySelector('.performer-placeholder');
                placeholder.classList.remove('bg-darkPrimairy/40');
                const image = dropzone.querySelector('img');
                if (image) image.classList.remove('opacity-20', 'scale-95');
            },
            ondrop: function (event) {
                const draggable = event.relatedTarget;
                const dropzone = event.target;
                const image = dropzone.querySelector('img');
                const placeholder = dropzone.querySelector('.performer-placeholder');

                // Get performer data
                const performerId = draggable.dataset.performerId;
                const performerName = draggable.dataset.performerName;
                const performerGender = draggable.dataset.performerGender;

                // Update visuals
                image.classList.add('opacity-20');
                placeholder.classList.add('bg-darkPrimairy/40');
                placeholder.innerHTML = `
                    <div class="text-center">
                        <div class="font-medium text-TextWhite">
                            ${performerName}
                        </div>
                        <div class="text-xs text-secondary mt-1">
                            ${performerGender}
                        </div>
                    </div>
                `;

                // Store assignment in cache
                const position = image.dataset.position;
                const type = image.dataset.type;
                setCache(`assignment_${type}_${position}`, {
                    performerId,
                    name: performerName,
                    gender: performerGender
                });

                // Remove the draggable element from DOM
                draggable.remove();
                
                dropzone.classList.remove('bg-secondary/20');
            }
        });
    }

    // Initial render
    renderSummary();

    // Update when cache changes
    window.addEventListener('storage', (e) => {
        if (e.key?.startsWith('performer') || e.key?.includes('Min') || e.key?.includes('Max')) {
            renderSummary();
        }
    });

    window.addEventListener('myCustomUpdate', () => {
        renderSummary(); // re-render on custom event
    });
});

