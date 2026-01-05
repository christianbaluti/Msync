document.addEventListener('DOMContentLoaded', function() {
    // --- Constants and State ---
    const PIXELS_PER_CM = 37.795; // Standard for 96 DPI
    let activeCanvas = null;
    let allMembersData = [];
    let currentDesignId = null;

    // --- DOM Elements ---
    const widthInput = document.getElementById('cardWidth');
    const heightInput = document.getElementById('cardHeight');
    const frontTabBtn = document.getElementById('front-tab-btn');
    const backTabBtn = document.getElementById('back-tab-btn');
    const frontCanvasWrapper = document.getElementById('front-canvas-wrapper');
    const backCanvasWrapper = document.getElementById('back-canvas-wrapper');
    const memberSearchInput = document.getElementById('member-search-input');
    const memberListContainer = document.getElementById('member-list-container');
    const propertiesPanel = document.getElementById('properties-panel');
    const layersPanel = document.getElementById('layers-panel');
    const generatePdfBtn = document.getElementById('generate-pdf-btn');
    const addTextBtn = document.getElementById('add-text-btn');
    const addImageBtn = document.getElementById('add-image-btn');
    const addQrCodeBtn = document.getElementById('add-qr-code-btn');
    const addShapeBtn = document.getElementById('add-shape-btn');
    const addPlaceholderBtn = document.getElementById('add-placeholder-btn');
    const placeholderSelect = document.getElementById('placeholder-select');

    // --- Save/Load DOM Elements ---
    const saveDesignBtn = document.getElementById('save-design-btn');
    const loadDesignBtn = document.getElementById('load-design-btn');
    const loadDesignModal = document.getElementById('load-design-modal');
    const designListContainer = document.getElementById('design-list-container');
    const closeModalBtn = document.getElementById('close-modal-btn');

    const imageUploadInput = document.createElement('input');
    imageUploadInput.type = 'file';
    imageUploadInput.accept = 'image/*';

    // --- Fabric.js Canvas Initialization ---
    const frontCanvas = new fabric.Canvas('frontCanvas', { backgroundColor: '#ffffff', preserveObjectStacking: true });
    const backCanvas = new fabric.Canvas('backCanvas', { backgroundColor: '#ffffff', preserveObjectStacking: true });

    // --- Core Functions ---
    const updateCanvasSize = () => {
        const width = parseFloat(widthInput.value) * PIXELS_PER_CM;
        const height = parseFloat(heightInput.value) * PIXELS_PER_CM;
        [frontCanvas, backCanvas].forEach(canvas => {
            if (width > 0 && height > 0) {
                canvas.setDimensions({ width, height }).renderAll();
            }
        });
    };

    const setActiveTab = (tab) => {
        const isFront = tab === 'front';
        frontTabBtn.classList.toggle('border-indigo-500', isFront);
        frontTabBtn.classList.toggle('text-indigo-600', isFront);
        backTabBtn.classList.toggle('border-indigo-500', !isFront);
        backTabBtn.classList.toggle('text-indigo-600', !isFront);
        frontCanvasWrapper.classList.toggle('hidden', !isFront);
        backCanvasWrapper.classList.toggle('hidden', isFront);
        activeCanvas = isFront ? frontCanvas : backCanvas;
        updateLayersPanel();
        updatePropertiesPanel(null);
    };

    // --- Member List Functions (Unchanged) ---
    const renderMemberList = (members) => {
        if (!members || members.length === 0) {
            memberListContainer.innerHTML = '<p class="text-gray-500 text-center py-4">No active members found.</p>';
            return;
        }
        let content = `<div class="border-b border-gray-200 pb-2 mb-2"><label class="flex items-center px-3"><input type="checkbox" id="select-all-members" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"><span class="ml-3 text-sm font-medium text-gray-800">Select All / Deselect All</span></label></div>`;
        content += members.map(member => `<div class="px-3 py-2 hover:bg-gray-50"><label class="flex items-center"><input type="checkbox" class="member-checkbox h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" value="${member.subscription_id}"><span class="ml-3 text-sm text-gray-700">${member.full_name} (${member.membership_card_number})</span></label></div>`).join('');
        memberListContainer.innerHTML = content;
        document.getElementById('select-all-members').addEventListener('change', (e) => {
            document.querySelectorAll('.member-checkbox').forEach(checkbox => checkbox.checked = e.target.checked);
        });
    };

    const filterMembers = () => {
        const searchTerm = memberSearchInput.value.toLowerCase();
        const filtered = !searchTerm ? allMembersData : allMembersData.filter(m => m.full_name.toLowerCase().includes(searchTerm) || m.membership_card_number.toLowerCase().includes(searchTerm) || (m.company_name && m.company_name.toLowerCase().includes(searchTerm)));
        renderMemberList(filtered);
    };

    const loadAllMembers = async () => {
        try {
            const response = await fetch(`/api/members/read_for_id.php`);
            const result = await response.json();
            if (result.success) {
                allMembersData = result.data;
                renderMemberList(allMembersData);
            } else { throw new Error(result.message); }
        } catch (error) {
            console.error('Error loading members:', error);
            memberListContainer.innerHTML = `<p class="text-red-500 text-center py-4">Could not load members: ${error.message}</p>`;
        }
    };
    
    // --- Properties & Layers Panels (Unchanged) ---
    const updatePropertiesPanel = (obj) => {
        if (!obj) { propertiesPanel.innerHTML = 'Select an object on the canvas to see its properties.'; return; }
        let content = '';
        const commonProps = `<div class="mb-2"><label class="block text-xs font-medium">Opacity</label><input type="range" min="0" max="1" step="0.01" value="${obj.opacity}" data-prop="opacity" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"></div>`;
        if (obj.type === 'textbox' || obj.isPlaceholder) {
            content += `<div class="mb-2"><label class="block text-xs font-medium">Text</label><textarea data-prop="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" ${obj.isPlaceholder ? 'disabled' : ''}>${obj.text}</textarea></div><div class="grid grid-cols-2 gap-2 mb-2"><div><label class="block text-xs font-medium">Font Size</label><input type="number" value="${obj.fontSize}" data-prop="fontSize" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm"></div><div><label class="block text-xs font-medium">Font Family</label><select data-prop="fontFamily" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm"><option value="Arial" ${obj.fontFamily === 'Arial' ? 'selected' : ''}>Arial</option><option value="Helvetica" ${obj.fontFamily === 'Helvetica' ? 'selected' : ''}>Helvetica</option><option value="Times New Roman" ${obj.fontFamily === 'Times New Roman' ? 'selected' : ''}>Times New Roman</option><option value="Courier New" ${obj.fontFamily === 'Courier New' ? 'selected' : ''}>Courier New</option></select></div></div><div class="mb-2"><label class="block text-xs font-medium">Fill Color</label><input type="color" value="${obj.fill}" data-prop="fill" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></div>`;
        } else if (obj.type === 'rect' || obj.type === 'circle' || obj.type === 'triangle' || obj.type === 'image') {
            content += `<div class="mb-2"><label class="block text-xs font-medium">Fill Color</label><input type="color" value="${obj.fill}" data-prop="fill" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></div>`;
        }
        propertiesPanel.innerHTML = content + commonProps;
        propertiesPanel.querySelectorAll('input, select, textarea').forEach(input => {
            input.addEventListener('input', (e) => {
                if (!activeCanvas.getActiveObject()) return;
                const prop = e.target.dataset.prop;
                let value = e.target.type === 'number' || e.target.type === 'range' ? parseFloat(e.target.value) : e.target.value;
                activeCanvas.getActiveObject().set(prop, value);
                activeCanvas.renderAll();
            });
        });
    };

    const updateLayersPanel = () => {
        if (!activeCanvas) return;
        const objects = activeCanvas.getObjects().slice().reverse();
        if (objects.length === 0) { layersPanel.innerHTML = 'Object layers will appear here.'; return; }
        layersPanel.innerHTML = objects.map((obj, index) => {
            const actualIndex = objects.length - 1 - index;
            let name = obj.type;
            if (obj.isPlaceholder) name = 'Placeholder';
            if (obj.isQrPlaceholder) name = 'QR Code';
            if (obj.text) name = `${name}: ${obj.text.substring(0, 15)}...`;
            return `<div class="flex items-center justify-between p-2 hover:bg-gray-100 rounded-md"><span class="text-sm truncate">${name}</span><div class="flex items-center space-x-1"><button title="Bring Forward" data-action="up" data-index="${actualIndex}" class="layer-btn p-1 text-gray-500 hover:text-gray-800">&uarr;</button><button title="Send Backward" data-action="down" data-index="${actualIndex}" class="layer-btn p-1 text-gray-500 hover:text-gray-800">&darr;</button><button title="Delete" data-action="delete" data-index="${actualIndex}" class="layer-btn p-1 text-red-500 hover:text-red-800">&times;</button></div></div>`;
        }).join('');
        layersPanel.querySelectorAll('.layer-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const action = e.currentTarget.dataset.action;
                const index = parseInt(e.currentTarget.dataset.index);
                const obj = activeCanvas.item(index);
                if (!obj) return;
                if (action === 'up') obj.bringForward();
                else if (action === 'down') obj.sendBackwards();
                else if (action === 'delete') activeCanvas.remove(obj);
                activeCanvas.renderAll();
            });
        });
    };

    // --- Add Element Functions ---
    addTextBtn.addEventListener('click', () => {
        const text = new fabric.Textbox('Sample Text', { left: 50, top: 50, width: 150, fontSize: 20, fontFamily: 'Arial', fill: '#000000' });
        activeCanvas.add(text).setActiveObject(text);
    });

    // **FIX 1: CLIENT-SIDE IMAGE RESIZING**
    addImageBtn.addEventListener('click', () => imageUploadInput.click());
    imageUploadInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = (f) => {
            const imgElement = new Image();
            imgElement.src = f.target.result;
            imgElement.onload = () => {
                const canvas = document.createElement('canvas');
                const MAX_WIDTH = 1000; // Max width for an uploaded image
                const MAX_HEIGHT = 1000; // Max height
                let width = imgElement.width;
                let height = imgElement.height;

                if (width > height) {
                    if (width > MAX_WIDTH) {
                        height *= MAX_WIDTH / width;
                        width = MAX_WIDTH;
                    }
                } else {
                    if (height > MAX_HEIGHT) {
                        width *= MAX_HEIGHT / height;
                        height = MAX_HEIGHT;
                    }
                }
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(imgElement, 0, 0, width, height);
                const dataUrl = canvas.toDataURL(file.type);

                fabric.Image.fromURL(dataUrl, (img) => {
                    img.scaleToWidth(200);
                    activeCanvas.add(img).centerObject(img).setActiveObject(img);
                });
            };
        };
        reader.readAsDataURL(file);
        e.target.value = '';
    });


    addShapeBtn.addEventListener('click', () => {
        const shape = new fabric.Rect({ left: 50, top: 50, width: 100, height: 100, fill: '#cccccc' });
        activeCanvas.add(shape).setActiveObject(shape);
    });
    
    addPlaceholderBtn.addEventListener('click', () => {
        const text = new fabric.Textbox(placeholderSelect.value, { left: 50, top: 50, width: 150, fontSize: 16, fontFamily: 'Arial', fill: '#000000', isPlaceholder: true, editable: false, });
        activeCanvas.add(text).setActiveObject(text);
    });
    
    // This now adds an image placeholder, just like your nametag designer
    addQrCodeBtn.addEventListener('click', () => {
        const tempDiv = document.createElement('div');
        new QRCode(tempDiv, { text: '{{member_id}}', width: 256, height: 256, correctLevel: QRCode.CorrectLevel.H });

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
    
    // --- PDF Generation Logic ---
    generatePdfBtn.addEventListener('click', async () => {
        const selectedSubscriptionIds = Array.from(document.querySelectorAll('.member-checkbox:checked')).map(cb => cb.value);
        if (selectedSubscriptionIds.length === 0) {
            alert('Please select at least one member.');
            return;
        }

        generatePdfBtn.textContent = `Generating...`;
        generatePdfBtn.disabled = true;

        // **FIX 2: ROBUST QR CODE GENERATION (Modeled on your nametag code)**
        const prepareCanvasForMember = async (canvas, member) => {
            const json = canvas.toJSON(['isPlaceholder', 'isQrPlaceholder']);
            const clonedCanvas = new fabric.Canvas(null);
            await new Promise(resolve => clonedCanvas.loadFromJSON(json, resolve));

            const objectsToRemove = [];
            const objectsToAdd = [];

            const processingPromises = clonedCanvas.getObjects().map(async (obj) => {
                if (!obj) return;
                if (obj.isPlaceholder) {
                    let text = 'N/A';
                    switch (obj.text) {
                        case '{{member_name}}': text = member.full_name || 'N/A'; break;
                        case '{{member_id}}': text = member.membership_card_number || 'N/A'; break;
                        case '{{membership_type}}': text = member.membership_type || 'N/A'; break;
                        case '{{expiry_date}}': text = member.end_date ? new Date(member.end_date).toLocaleDateString() : 'N/A'; break;
                        case '{{company_name}}': text = member.company_name || 'N/A'; break;
                    }
                    obj.set('text', text);
                } else if (obj.isQrPlaceholder) {
                    const tempDiv = document.createElement('div');
                    new QRCode(tempDiv, { text: member.membership_card_number || 'NO-ID', width: 256, height: 256, correctLevel: QRCode.CorrectLevel.H });
                    
                    const qrCanvas = await new Promise(resolve => setTimeout(() => resolve(tempDiv.querySelector('canvas')), 50));
                    const dataUrl = qrCanvas.toDataURL('image/png');

                    const newQrImg = await new Promise(resolve => fabric.Image.fromURL(dataUrl, img => resolve(img)));
                    
                    newQrImg.set({ left: obj.left, top: obj.top, angle: obj.angle, scaleX: obj.scaleX, scaleY: obj.scaleY, });
                    
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
            for (let i = 0; i < selectedSubscriptionIds.length; i++) {
                 const subId = selectedSubscriptionIds[i];
                 generatePdfBtn.textContent = `Generating (${i + 1}/${selectedSubscriptionIds.length})...`;
                 const member = allMembersData.find(m => m.subscription_id == subId);

                 if (member) {
                     const frontDesign = await prepareCanvasForMember(frontCanvas, member);
                     const backDesign = await prepareCanvasForMember(backCanvas, member);
                     finalDesigns.push({ front_design_json: JSON.stringify(frontDesign), back_design_json: JSON.stringify(backDesign) });
                 }
            }

            const payload = {
                width_cm: parseFloat(widthInput.value),
                height_cm: parseFloat(heightInput.value),
                designs: finalDesigns
            };
            
            const response = await fetch('/api/members/generate_ids.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });

            if (!response.ok) {
                const errorResult = await response.json().catch(() => ({ message: `PDF generation failed with status ${response.status}` }));
                throw new Error(errorResult.message);
            }

            const blob = await response.blob();
            if (blob.type !== 'application/pdf') {
                throw new Error('Server did not return a valid PDF file. Check server logs.');
            }

            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = `membership-cards.pdf`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            a.remove();
        } catch (error) {
            console.error('PDF Generation Error:', error);
            alert('An error occurred: ' + error.message);
        } finally {
            generatePdfBtn.textContent = 'Generate PDF for Selected Members';
            generatePdfBtn.disabled = false;
        }
    });

    // --- Save/Load and Initial Setup (Unchanged) ---
    if(saveDesignBtn) {
        saveDesignBtn.addEventListener('click', async () => {
            const designName = prompt("Enter a name for this design:", "My Card Design");
            if (!designName) return;
            const payload = { design_name: designName, front_json: JSON.stringify(frontCanvas.toJSON(['isPlaceholder', 'isQrPlaceholder'])), back_json: JSON.stringify(backCanvas.toJSON(['isPlaceholder', 'isQrPlaceholder'])) };
            const url = currentDesignId ? `/api/card_designs/update.php?id=${currentDesignId}` : '/api/card_designs/create.php';
            try {
                const response = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                const result = await response.json();
                if (result.success) { alert('Design saved successfully!'); if(result.id) currentDesignId = result.id; } else { throw new Error(result.message); }
            } catch (error) { console.error('Error saving design:', error); alert('Could not save design: ' + error.message); }
        });
    }

    if(loadDesignBtn) {
        loadDesignBtn.addEventListener('click', async () => {
            try {
                const response = await fetch('/api/card_designs/read.php');
                const result = await response.json();
                if (result.success) {
                    designListContainer.innerHTML = result.data.length > 0 ? result.data.map(d => `<div class="p-2 hover:bg-gray-200 cursor-pointer" data-id="${d.id}">${d.design_name}</div>`).join('') : '<p class="p-2 text-gray-500">No saved designs found.</p>';
                    loadDesignModal.classList.remove('hidden');
                } else { throw new Error(result.message); }
            } catch (error) { alert('Could not load designs: ' + error.message); }
        });
    }

    if(designListContainer) {
        designListContainer.addEventListener('click', async (e) => {
            if(e.target.dataset.id) {
                const designId = e.target.dataset.id;
                try {
                    const response = await fetch(`/api/card_designs/read_one.php?id=${designId}`);
                    const result = await response.json();
                    if(result.success) {
                        const design = result.data;
                        frontCanvas.loadFromJSON(design.front_json, frontCanvas.renderAll.bind(frontCanvas));
                        backCanvas.loadFromJSON(design.back_json, backCanvas.renderAll.bind(backCanvas));
                        currentDesignId = design.id;
                        loadDesignModal.classList.add('hidden');
                        alert(`Design "${design.design_name}" loaded.`);
                    } else { throw new Error(result.message); }
                } catch(error) { alert('Failed to load the selected design: ' + error.message); }
            }
        });
    }
    
    if(closeModalBtn) { closeModalBtn.addEventListener('click', () => loadDesignModal.classList.add('hidden')); }

    const setupCanvasListeners = (canvas) => {
        canvas.on({
            'selection:created': (e) => updatePropertiesPanel(e.target),
            'selection:updated': (e) => updatePropertiesPanel(e.target),
            'selection:cleared': () => updatePropertiesPanel(null),
            'object:added': updateLayersPanel,
            'object:removed': updateLayersPanel,
            'object:modified': () => { updateLayersPanel(); updatePropertiesPanel(activeCanvas.getActiveObject()); },
        });
    };

    [frontCanvas, backCanvas].forEach(setupCanvasListeners);
    widthInput.addEventListener('change', updateCanvasSize);
    heightInput.addEventListener('change', updateCanvasSize);
    frontTabBtn.addEventListener('click', () => setActiveTab('front'));
    backTabBtn.addEventListener('click', () => setActiveTab('back'));
    memberSearchInput.addEventListener('input', filterMembers);
    
    updateCanvasSize();
    setActiveTab('front');
    loadAllMembers();
});