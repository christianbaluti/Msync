document.addEventListener('DOMContentLoaded', function() {
    // --- Constants and State ---
    const PIXELS_PER_CM = 37.795; // Conversion factor for 96 DPI
    const urlParams = new URLSearchParams(window.location.search);
    const eventId = urlParams.get('id');
    let activeCanvas = null;
    let attendeeData = []; // Store fetched attendee data

    // --- DOM Elements ---
    const widthInput = document.getElementById('tagWidth');
    const heightInput = document.getElementById('tagHeight');
    const frontTabBtn = document.getElementById('front-tab-btn');
    const backTabBtn = document.getElementById('back-tab-btn');
    const frontCanvasWrapper = document.getElementById('front-canvas-wrapper');
    const backCanvasWrapper = document.getElementById('back-canvas-wrapper');
    const attendeeListContainer = document.getElementById('attendee-list-container');
    const propertiesPanel = document.getElementById('properties-panel');
    const layersPanel = document.getElementById('layers-panel');
    const generatePdfBtn = document.getElementById('generate-pdf-btn');

    // NEW: Filter Elements
    const attendeeSearchInput = document.getElementById('attendee-search');
    const attendeeTypeFilter = document.getElementById('attendee-type-filter');

    // Toolbar buttons
    const addTextBtn = document.getElementById('add-text-btn');
    const addImageBtn = document.getElementById('add-image-btn');
    const addQrCodeBtn = document.getElementById('add-qr-code-btn');
    const addShapeBtn = document.getElementById('add-shape-btn');
    const addPlaceholderBtn = document.getElementById('add-placeholder-btn');
    const placeholderSelect = document.getElementById('placeholder-select');

    // Hidden input for image uploads
    const imageUploadInput = document.createElement('input');
    imageUploadInput.type = 'file';
    imageUploadInput.accept = 'image/*';

    // --- Fabric.js Canvas Initialization ---
    const frontCanvas = new fabric.Canvas('frontCanvas', {
        backgroundColor: '#ffffff',
        preserveObjectStacking: true
    });
    const backCanvas = new fabric.Canvas('backCanvas', {
        backgroundColor: '#ffffff',
        preserveObjectStacking: true
    });

    const formatDate = (dateString, type = 'datetime') => {
        if (!dateString) return 'N/A';
        const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
        if (type !== 'datetime') {
            delete options.hour;
            delete options.minute;
        }
        return new Date(dateString).toLocaleString('en-GB', options);
    };

    const renderHeader = (details) => {
        const eventHeaderContainer = document.getElementById('eventHeader');
        if (!eventHeaderContainer || !details) return;

        const statusBadge = details.status === 'published' 
            ? '<span class="inline-flex items-center rounded-md bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Published</span>'
            : '<span class="inline-flex items-center rounded-md bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-700">Draft</span>';

        eventHeaderContainer.innerHTML = `
            <div class="md:flex md:items-center md:justify-between">
                <div class="min-w-0 flex-1">
                    <h1 class="text-3xl font-bold leading-tight text-slate-900 sm:truncate">${details.title}</h1>
                    <div class="mt-2 flex flex-col sm:flex-row sm:flex-wrap sm:space-x-6 text-slate-500">
                        <div class="mt-2 flex items-center text-sm">
                            <i class="fa-regular fa-calendar-days mr-1.5 h-5 w-5 flex-shrink-0 text-slate-400"></i>
                            ${formatDate(details.start_datetime)} to ${formatDate(details.end_datetime)}
                        </div>
                        <div class="mt-2 flex items-center text-sm">
                            <i class="fa-solid fa-location-dot mr-1.5 h-5 w-5 flex-shrink-0 text-slate-400"></i>
                            ${details.location || 'N/A'}
                        </div>
                        <div class="mt-2 flex items-center text-sm">${statusBadge}</div>
                    </div>
                </div>
            </div>
            <div class="mt-4 prose prose-sm max-w-none text-slate-600">${details.description || ''}</div>
        `;
    };

    // --- Core Functions (Sizing, Tabs, Data Loading) ---
    const updateCanvasSize = () => {
        const width = parseFloat(widthInput.value) * PIXELS_PER_CM;
        const height = parseFloat(heightInput.value) * PIXELS_PER_CM;

        if (width > 0 && height > 0) {
            [frontCanvas, backCanvas].forEach(canvas => {
                canvas.setDimensions({
                    width: width,
                    height: height
                });
                canvas.renderAll();
            });
        }
    };

    const setActiveTab = (tab) => {
        const isFront = tab === 'front';
        frontTabBtn.classList.toggle('border-indigo-500', isFront);
        frontTabBtn.classList.toggle('text-indigo-600', isFront);
        frontTabBtn.classList.toggle('text-gray-500', !isFront);
        frontCanvasWrapper.classList.toggle('hidden', !isFront);

        backTabBtn.classList.toggle('border-indigo-500', !isFront);
        backTabBtn.classList.toggle('text-indigo-600', !isFront);
        backTabBtn.classList.toggle('text-gray-500', isFront);
        backCanvasWrapper.classList.toggle('hidden', isFront);

        activeCanvas = isFront ? frontCanvas : backCanvas;
        updateLayersPanel();
        updatePropertiesPanel(null);
    };

    // --- NEW: Attendee Filter Functions ---
    const populateAttendeeTypeFilter = () => {
        const types = [...new Set(attendeeData.map(a => a.attendee_type).filter(Boolean))]; // Get unique, non-null types
        attendeeTypeFilter.innerHTML = '<option value="">All Types</option>'; // Reset
        types.sort().forEach(type => {
            const option = document.createElement('option');
            option.value = type;
            option.textContent = type;
            attendeeTypeFilter.appendChild(option);
        });
    };

    const applyAttendeeFilters = () => {
        const searchTerm = attendeeSearchInput.value.toLowerCase();
        const selectedType = attendeeTypeFilter.value;

        const filteredAttendees = attendeeData.filter(attendee => {
            const nameMatch = attendee.full_name.toLowerCase().includes(searchTerm);
            const companyMatch = (attendee.company_name || '').toLowerCase().includes(searchTerm);
            const typeMatch = !selectedType || attendee.attendee_type === selectedType;

            return (nameMatch || companyMatch) && typeMatch;
        });

        renderAttendeeList(filteredAttendees);
    };
    // --- End NEW Functions ---


    const renderAttendeeList = (attendees) => {
        if (!attendees || attendees.length === 0) {
            // MODIFIED: More specific message
            attendeeListContainer.innerHTML = '<p class="text-gray-500 text-center py-4">No attendees match the current filters.</p>';
            return;
        }

        let content = `<div class="border-b border-gray-200 pb-2 mb-2">
            <label class="flex items-center px-3 py-2">
                <input type="checkbox" id="select-all-attendees" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                <span class="ml-3 text-sm font-medium text-gray-800">Select All / Deselect All (Visible)</span>
            </label>
        </div>`;

        content += attendees.map(attendee => `
            <div class="px-3 py-2 hover:bg-gray-50">
                <label class="flex items-center">
                    <input type="checkbox" class="attendee-checkbox h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600" value="${attendee.ticket_id}">
                    <span class="ml-3 text-sm text-gray-700">${attendee.full_name} (${attendee.company_name || 'N/A'})</span>
                    <span class="ml-auto text-xs text-gray-500 pr-2">${attendee.attendee_type || ''}</span>
                </label>
            </div>
        `).join('');

        attendeeListContainer.innerHTML = content;

        document.getElementById('select-all-attendees').addEventListener('change', (e) => {
            // This correctly selects only the checkboxes rendered (i.e., the filtered ones)
            document.querySelectorAll('.attendee-checkbox').forEach(checkbox => {
                checkbox.checked = e.target.checked;
            });
        });
    };

    const loadEventDetails = async () => {
        if (!eventId) {
            document.getElementById('eventHeader').innerHTML = '<p class="text-red-500">Error: No Event ID provided.</p>';
            return;
        }
        try {
            const response = await fetch(`/api/events/read_details.php?id=${eventId}`);
            if (!response.ok) throw new Error('Network response was not ok');
            
            const result = await response.json();
            if (result.details) {
                renderHeader(result.details);
            } else {
                throw new Error('Event details could not be found in the API response.');
            }
        } catch (error) {
            console.error('Error loading event details:', error);
            document.getElementById('eventHeader').innerHTML = `<p class="text-red-500">Could not load event details: ${error.message}</p>`;
        }
    };

    const loadAttendees = async () => {
        if (!eventId) {
            attendeeListContainer.innerHTML = '<p class="text-red-500">Error: No Event ID provided.</p>';
            return;
        }
        try {
            const response = await fetch(`/api/events/purchases/read_for_nametags.php?id=${eventId}`);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            const data = await response.json();
            if (data.success) {
                attendeeData = data.attendees; // Store the master list
                renderAttendeeList(data.attendees); // Render the full list initially
                populateAttendeeTypeFilter(); // NEW: Populate the filter dropdown
            } else {
                throw new Error(data.message || 'Failed to load attendees.');
            }
        } catch (error) {
            console.error('Error loading attendees:', error);
            attendeeListContainer.innerHTML = `<p class="text-red-500">Could not load attendees: ${error.message}</p>`;
        }
    };

    // --- Properties Panel Functions ---
    const updatePropertiesPanel = (obj) => {
        if (!obj) {
            propertiesPanel.innerHTML = '<p class="text-sm text-gray-500">Select an object to edit its properties.</p>';
            return;
        }

        let content = `<div class="space-y-4">`;
        if (obj.type !== 'image' || !obj.isQrPlaceholder) {
             content += `
                <div>
                    <label class="block text-sm font-medium text-gray-700">Fill Color</label>
                    <input type="color" class="mt-1 w-full h-8 p-0 border-gray-300 rounded-md" data-prop="fill" value="${obj.get('fill') || '#000000'}">
                </div>`;
        }
        content += `
            <div>
                <label class="block text-sm font-medium text-gray-700">Opacity</label>
                <input type="range" class="mt-1 w-full" min="0" max="1" step="0.01" data-prop="opacity" value="${obj.get('opacity')}">
            </div>`;

        if (obj.type === 'textbox') {
            content += `
                <div>
                    <label class="block text-sm font-medium text-gray-700">Text</label>
                    <textarea class="mt-1 w-full border rounded-md p-1 text-sm shadow-sm" data-prop="text">${obj.get('text')}</textarea>
                </div>`;
            content += `
                <div>
                    <label class="block text-sm font-medium text-gray-700">Font Size</label>
                    <input type="number" class="mt-1 w-full border rounded-md p-1 text-sm shadow-sm" data-prop="fontSize" value="${obj.get('fontSize')}">
                </div>`;
        }
        if (obj.type === 'rect' || obj.type === 'circle' || obj.type === 'triangle') {
            content += `
                <div>
                    <label class="block text-sm font-medium text-gray-700">Stroke Color</label>
                    <input type="color" class="mt-1 w-full h-8 p-0 border-gray-300 rounded-md" data-prop="stroke" value="${obj.get('stroke') || '#000000'}">
                </div>`;
            content += `
                <div>
                    <label class="block text-sm font-medium text-gray-700">Stroke Width</label>
                    <input type="number" class="mt-1 w-full border rounded-md p-1 text-sm shadow-sm" data-prop="strokeWidth" value="${obj.get('strokeWidth')}">
                </div>`;
        }
        content += `</div>`;
        propertiesPanel.innerHTML = content;

        propertiesPanel.querySelectorAll('input, textarea, select').forEach(input => {
            input.addEventListener('input', (e) => {
                const prop = e.target.dataset.prop;
                const value = e.target.type === 'number' || e.target.type === 'range' ? parseFloat(e.target.value) : e.target.value;
                const activeObject = activeCanvas.getActiveObject();
                if (activeObject) {
                    activeObject.set(prop, value);
                    activeCanvas.renderAll();
                }
            });
        });
    };

    // --- Layers Panel Functions ---
    const updateLayersPanel = () => {
        if (!activeCanvas) return;
        layersPanel.innerHTML = '';
        const objects = activeCanvas.getObjects().slice().reverse(); // Top layer first

        if (objects.length === 0) {
            layersPanel.innerHTML = '<p class="text-sm text-gray-500">No objects on this canvas.</p>';
            return;
        }

        objects.forEach((obj, index) => {
            const layerDiv = document.createElement('div');
            layerDiv.className = 'flex items-center justify-between p-2 rounded-md hover:bg-gray-100';
            if (activeCanvas.getActiveObject() === obj) {
                layerDiv.classList.add('bg-indigo-100');
            }

            layerDiv.addEventListener('click', () => activeCanvas.setActiveObject(obj).renderAll());

            const objectName = obj.isPlaceholder ? `{{ ${obj.text.replace(/{{|}}/g, "")} }}` : (obj.isQrPlaceholder ? "QR Code" : (obj.type || 'object'));
            layerDiv.innerHTML = `
                <span class="text-sm truncate cursor-pointer flex-grow">${objectName}</span>
                <div class="space-x-1 flex-shrink-0">
                    <button data-action="up" class="p-1 rounded-full text-gray-500 hover:bg-gray-200 hover:text-black" title="Bring Forward">&uarr;</button>
                    <button data-action="down" class="p-1 rounded-full text-gray-500 hover:bg-gray-200 hover:text-black" title="Send Backward">&darr;</button>
                    <button data-action="delete" class="p-1 rounded-full text-red-500 hover:bg-red-100 hover:text-red-700" title="Delete">&times;</button>
                </div>`;

            layerDiv.querySelector('[data-action="up"]').addEventListener('click', (e) => { e.stopPropagation(); activeCanvas.bringForward(obj).renderAll(); });
            layerDiv.querySelector('[data-action="down"]').addEventListener('click', (e) => { e.stopPropagation(); activeCanvas.sendBackwards(obj).renderAll(); });
            layerDiv.querySelector('[data-action="delete"]').addEventListener('click', (e) => { e.stopPropagation(); activeCanvas.remove(obj).renderAll(); });

            layersPanel.appendChild(layerDiv);
        });
    };

    // --- Toolbar Event Listeners ---
    addTextBtn.addEventListener('click', () => {
        const text = new fabric.Textbox('Sample Text', {
            left: 50, top: 50, fontSize: 20, width: 150, fill: '#333333'
        });
        activeCanvas.add(text).setActiveObject(text);
    });

    addPlaceholderBtn.addEventListener('click', () => {
        const text = new fabric.Textbox(placeholderSelect.value, {
            left: 50, top: 50, fontSize: 20, width: 150, fill: '#005a9c', isPlaceholder: true
        });
        activeCanvas.add(text).setActiveObject(text);
    });

    addQrCodeBtn.addEventListener('click', () => {
        const tempDiv = document.createElement('div');
        new QRCode(tempDiv, {
            text: '{{ticket_code}}', // Placeholder text for generation
            width: 256,
            height: 256,
            correctLevel: QRCode.CorrectLevel.H
        });

        // Wait a moment for the QR code to be generated by the library
        setTimeout(() => {
            const qrCanvas = tempDiv.querySelector('canvas');
            const dataUrl = qrCanvas.toDataURL('image/png');
            
            fabric.Image.fromURL(dataUrl, (img) => {
                img.set({ left: 100, top: 100, isQrPlaceholder: true });
                img.scaleToWidth(100);
                activeCanvas.add(img).setActiveObject(img);
            });
        }, 100);
    });

    addShapeBtn.addEventListener('click', () => {
        const rect = new fabric.Rect({
            left: 100, top: 100, width: 100, height: 100, fill: '#cccccc', stroke: '#333333', strokeWidth: 2
        });
        activeCanvas.add(rect).setActiveObject(rect);
    });

    addImageBtn.addEventListener('click', () => imageUploadInput.click());
    imageUploadInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = (f) => {
            fabric.Image.fromURL(f.target.result, (img) => {
                img.scaleToWidth(200);
                activeCanvas.add(img).centerObject(img).setActiveObject(img);
            });
        };
        reader.readAsDataURL(file);
        e.target.value = '';
    });

    // --- Canvas Event Listeners ---
    [frontCanvas, backCanvas].forEach(canvas => {
        canvas.on({
            'selection:created': (e) => updatePropertiesPanel(e.target),
            'selection:updated': (e) => updatePropertiesPanel(e.target),
            'selection:cleared': () => updatePropertiesPanel(null),
            'object:added': updateLayersPanel,
            'object:removed': updateLayersPanel,
            'object:modified': updateLayersPanel,
            'text:changed': (e) => updatePropertiesPanel(e.target),
            'after:render': updateLayersPanel // Re-render layers on any change
        });
    });

    // --- Main PDF Generation Logic ---
    generatePdfBtn.addEventListener('click', async () => {
        const selectedTicketIds = Array.from(document.querySelectorAll('.attendee-checkbox:checked')).map(cb => cb.value);

        if (selectedTicketIds.length === 0) {
            alert('Please select at least one attendee to generate nametags for.');
            return;
        }

        const originalBtnText = generatePdfBtn.innerHTML;
        generatePdfBtn.innerHTML = `Generating...`;
        generatePdfBtn.disabled = true;
        
        // This function prepares a canvas design for a specific attendee
        const prepareCanvasForAttendee = async (canvas, attendee) => {
            const json = canvas.toJSON(['isPlaceholder', 'isQrPlaceholder']);
            const clonedCanvas = new fabric.Canvas(null);

            // Wait for the canvas to be fully loaded from the JSON data
            await new Promise(resolve => {
                clonedCanvas.loadFromJSON(json, () => resolve());
            });

            const objectsToRemove = [];
            const objectsToAdd = [];

            // Use Promise.all to handle all async operations (like QR code generation) inside the loop
            const processingPromises = clonedCanvas.getObjects().map(async (obj) => {
                if (!obj) return; // Defensive check

                if (obj.isPlaceholder) {
                    let text = 'N/A';
                    switch (obj.text) {
                        case '{{user_name}}': text = attendee.full_name || 'N/A'; break;
                        case '{{company_name}}': text = attendee.company_name || 'N/A'; break;
                        case '{{ticket_code}}': text = attendee.ticket_code || 'N/A'; break;
                        case '{{attendee_type}}': text = attendee.attendee_type || 'N/A'; break;
                    }
                    obj.set('text', text);
                } else if (obj.isQrPlaceholder) {
                    const tempDiv = document.createElement('div');
                    new QRCode(tempDiv, {
                        text: attendee.ticket_code || 'NO-CODE',
                        width: 256,
                        height: 256,
                        correctLevel: QRCode.CorrectLevel.H
                    });

                    const qrCanvas = await new Promise(resolve => setTimeout(() => resolve(tempDiv.querySelector('canvas')), 100));
                    const dataUrl = qrCanvas.toDataURL('image/png');

                    const newQrImg = await new Promise(resolve => {
                        fabric.Image.fromURL(dataUrl, img => {
                            resolve(img); 
                        });
                    });
                    
                    newQrImg.set({
                        left: obj.left,
                        top: obj.top,
                        angle: obj.angle,
                        scaleX: obj.scaleX,
                        scaleY: obj.scaleY,
                    });

                    objectsToRemove.push(obj);
                    objectsToAdd.push(newQrImg);
                }
            });

            await Promise.all(processingPromises);

            objectsToRemove.forEach(obj => clonedCanvas.remove(obj));
            objectsToAdd.forEach(obj => clonedCanvas.add(obj));

            clonedCanvas.renderAll();
            return clonedCanvas.toJSON(['isPlaceholder', 'isQrPlaceholder']);
        };

        try {
            const finalDesigns = [];
            for (let i = 0; i < selectedTicketIds.length; i++) {
                const ticketId = selectedTicketIds[i];
                generatePdfBtn.innerHTML = `Generating (${i + 1}/${selectedTicketIds.length})...`;
                
                const attendee = attendeeData.find(a => a.ticket_id == ticketId);

                if (attendee) {
                    const frontDesign = await prepareCanvasForAttendee(frontCanvas, attendee);
                    const backDesign = await prepareCanvasForAttendee(backCanvas, attendee);
                    finalDesigns.push({
                        ticket_id: ticketId,
                        front_design_json: JSON.stringify(frontDesign),
                        back_design_json: JSON.stringify(backDesign)
                    });
                }
            }

            const payload = {
                event_id: eventId,
                width_cm: parseFloat(widthInput.value),
                height_cm: parseFloat(heightInput.value),
                designs: finalDesigns
            };

            const response = await fetch('/api/events/nametags/generate.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`Server error: ${response.status} ${response.statusText}. Details: ${errorText}`);
            }

            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = `nametags-event-${eventId}.pdf`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            a.remove();

        } catch (error) {
            console.error('PDF Generation Error:', error);
            alert('An error occurred while generating the PDF: ' + error.message);
        } finally {
            generatePdfBtn.innerHTML = originalBtnText;
            generatePdfBtn.disabled = false;
        }
    });

    // --- Initial Setup ---
    widthInput.addEventListener('change', updateCanvasSize);
    heightInput.addEventListener('change', updateCanvasSize);
    frontTabBtn.addEventListener('click', () => setActiveTab('front'));
    backTabBtn.addEventListener('click', () => setActiveTab('back'));
    
    // NEW: Add event listeners for filters
    attendeeSearchInput.addEventListener('input', applyAttendeeFilters);
    attendeeTypeFilter.addEventListener('change', applyAttendeeFilters);

    updateCanvasSize();
    setActiveTab('front');
    loadAttendees(); // This will now load data AND populate filters
    loadEventDetails()
});