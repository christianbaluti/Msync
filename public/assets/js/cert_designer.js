// /assets/js/cert_designer.js

document.addEventListener('DOMContentLoaded', function () {

    // --- UI Helpers (Modals & Toasts) ---
    const toast = document.getElementById('toast');
    const genericConfirmModal = document.getElementById('genericConfirmModal');
    const confirmModalTitle = document.getElementById('confirmModalTitle');
    const confirmModalMessage = document.getElementById('confirmModalMessage');
    const confirmModalConfirmBtn = document.getElementById('confirmModalConfirmBtn');
    let confirmAction = null;

    const showToast = (message, isError = false) => {
        if (!toast) return;
        toast.textContent = message;
        toast.className = `fixed top-5 right-5 z-[101] text-white py-2 px-4 rounded-lg shadow-md transition-opacity duration-300 ${isError ? 'bg-red-600' : 'bg-green-700'}`;
        toast.classList.remove('hidden');
        toast.classList.remove('opacity-0');
        setTimeout(() => {
            toast.classList.add('opacity-0');
            setTimeout(() => toast.classList.add('hidden'), 300);
        }, 3500);
    };

    const showGenericConfirm = (config) => {
        if (!genericConfirmModal) {
            // Fallback if modal DOM is missing
            if (confirm(config.message.replace(/<[^>]*>/g, ''))) config.onConfirm();
            return;
        }
        confirmModalTitle.textContent = config.title || 'Confirm Action';
        confirmModalMessage.innerHTML = config.message || 'Are you sure?';
        confirmModalConfirmBtn.textContent = config.confirmText || 'Confirm';

        // Remove old listeners to prevent stacking
        const newBtn = confirmModalConfirmBtn.cloneNode(true);
        confirmModalConfirmBtn.parentNode.replaceChild(newBtn, confirmModalConfirmBtn);

        newBtn.addEventListener('click', () => {
            if (typeof config.onConfirm === 'function') config.onConfirm();
            genericConfirmModal.classList.add('hidden');
        });

        genericConfirmModal.classList.remove('hidden');
    };

    // Close Modal Listeners
    document.querySelectorAll('.close-modal').forEach(btn => {
        btn.addEventListener('click', () => {
            btn.closest('.fixed').classList.add('hidden');
        });
    });

    // --- Utils ---
    function getQueryParam(name) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(name);
    }
    const eventId = getQueryParam('id');

    // --- Header Data Loading ---
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
            <div class="md:flex md:items-center md:justify-between mb-4">
                <div class="min-w-0 flex-1">
                    <h1 class="text-3xl font-bold leading-tight text-slate-900 sm:truncate">${details.title}</h1>
                    <div class="mt-2 flex flex-col sm:flex-row sm:flex-wrap sm:space-x-6 text-slate-500">
                        <div class="mt-2 flex items-center text-sm">
                            <i class="fa-regular fa-calendar-days mr-1.5 text-slate-400"></i>
                            ${formatDate(details.start_datetime)} to ${formatDate(details.end_datetime)}
                        </div>
                        <div class="mt-2 flex items-center text-sm">
                            <i class="fa-solid fa-location-dot mr-1.5 text-slate-400"></i>
                            ${details.location || 'N/A'}
                        </div>
                        <div class="mt-2 flex items-center text-sm">${statusBadge}</div>
                    </div>
                </div>
            </div>
        `;
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
            if (result.details) renderHeader(result.details);
        } catch (error) {
            console.error('Error loading event details:', error);
            document.getElementById('eventHeader').innerHTML = `<p class="text-red-500">Could not load event details.</p>`;
        }
    };

    loadEventDetails();

    // --- Canvas Setup ---
    const canvas = new fabric.Canvas('certCanvas', {
        preserveObjectStacking: true,
        backgroundColor: '#ffffff'
    });

    // UI Refs
    const wrapperOuter = document.getElementById('canvas-container-outer');
    const widthInput = document.getElementById('certWidth');
    const heightInput = document.getElementById('certHeight');
    const applySizeBtn = document.getElementById('apply-size-btn');
    const zoomLevelFn = document.getElementById('zoom-level');

    const PX_PER_CM = 37.795;

    function setCanvasSizeFromCM() {
        const wCM = parseFloat(widthInput.value) || 29.7;
        const hCM = parseFloat(heightInput.value) || 21.0;
        const wPX = Math.round(wCM * PX_PER_CM);
        const hPX = Math.round(hCM * PX_PER_CM);

        canvas.setWidth(wPX);
        canvas.setHeight(hPX);
        canvas.renderAll();

        // Auto fit first time?
        // Let's call fitCanvas.
        setTimeout(fitCanvas, 50);
    }

    applySizeBtn.addEventListener('click', () => {
        setCanvasSizeFromCM();
        showToast('Canvas size updated.');
    });

    // --- Zoom & Fit ---
    function updateZoomDisplay() {
        if (zoomLevelFn) zoomLevelFn.innerText = Math.round(canvas.getZoom() * 100) + '%';
    }

    function setZoom(scale) {
        // Center zoom relative to canvas center? 
        // Simple scaling:
        // Limit zoom
        if (scale < 0.1) scale = 0.1;
        if (scale > 5) scale = 5;
        canvas.setZoom(scale);

        // Resize viewport (the visible <canvas> element size) to match?
        // The pattern for zooming usually involves keeping viewport same (scroll) OR resizing viewport.
        // Given I put it in "overflow-auto", resizing viewport is correct to enable scrolling.

        const originalW = parseFloat(widthInput.value) * PX_PER_CM;
        const originalH = parseFloat(heightInput.value) * PX_PER_CM;
        canvas.setWidth(originalW * scale);
        canvas.setHeight(originalH * scale);
        canvas.renderAll();
        updateZoomDisplay();
    }

    function fitCanvas() {
        if (!wrapperOuter) return;
        // padding 64px from p-8
        const availW = wrapperOuter.clientWidth - 64;
        const availH = wrapperOuter.clientHeight - 64;

        const originalW = parseFloat(widthInput.value) * PX_PER_CM;
        const originalH = parseFloat(heightInput.value) * PX_PER_CM;

        if (originalW <= 0 || originalH <= 0) return;

        const scaleX = availW / originalW;
        const scaleY = availH / originalH;

        let scale = Math.min(scaleX, scaleY);
        if (scale > 1) scale = 1; // Don't upscale
        if (scale < 0.1) scale = 0.1;

        setZoom(scale);
    }

    document.getElementById('zoom-in-btn').addEventListener('click', () => setZoom(canvas.getZoom() * 1.1));
    document.getElementById('zoom-out-btn').addEventListener('click', () => setZoom(canvas.getZoom() * 0.9));
    document.getElementById('zoom-fit-btn').addEventListener('click', fitCanvas);

    // Init
    setCanvasSizeFromCM();

    // --- Layers List & Selection ---
    const layerList = document.getElementById('layers-list');

    function updateLayerList() {
        if (!layerList) return;
        layerList.innerHTML = '';
        const objs = canvas.getObjects();

        // Reverse order so top layer is at top of list
        for (let i = objs.length - 1; i >= 0; i--) {
            const obj = objs[i];
            let name = obj.type;
            if (obj.type === 'i-text' || obj.type === 'text') name = `T: "${(obj.text || '').substring(0, 15)}..."`;
            else if (obj.type === 'image') name = "Image";

            const div = document.createElement('div');
            const isActive = (canvas.getActiveObject() === obj);

            div.className = `flex items-center justify-between p-2 rounded text-xs cursor-pointer ${isActive ? 'bg-indigo-50 border border-indigo-200' : 'hover:bg-slate-50 border border-transparent'}`;

            div.innerHTML = `
                <span class="truncate font-medium ${isActive ? 'text-indigo-700' : 'text-gray-600'}">${name}</span>
                <div class="flex gap-1 opacity-0 group-hover:opacity-100 ${isActive ? 'opacity-100' : ''}">
                    <button class="layer-up px-1 hover:text-black" title="Bring Forward">&uarr;</button>
                    <button class="layer-down px-1 hover:text-black" title="Send Back">&darr;</button>
                </div>
            `;

            div.onclick = () => { canvas.setActiveObject(obj); canvas.renderAll(); };
            div.querySelector('.layer-up').onclick = (e) => { e.stopPropagation(); obj.bringForward(); canvas.renderAll(); updateLayerList(); };
            div.querySelector('.layer-down').onclick = (e) => { e.stopPropagation(); obj.sendBackwards(); canvas.renderAll(); updateLayerList(); };

            // Allow hover effect to show buttons
            div.classList.add('group');
            layerList.appendChild(div);
        }

        if (objs.length === 0) layerList.innerHTML = '<p class="text-xs text-gray-400 italic text-center p-2">Empty</p>';
    }

    ['object:added', 'object:removed', 'object:modified', 'selection:updated', 'selection:created', 'selection:cleared'].forEach(evt => {
        canvas.on(evt, () => {
            updateLayerList();
            updateProps(canvas.getActiveObject());
        });
    });

    // --- Properties Panel ---
    const propertiesPanel = document.getElementById('properties-panel');
    const objectControls = document.getElementById('object-controls');

    // Inputs
    const objColor = document.getElementById('obj-color');
    const objColorHex = document.getElementById('obj-color-hex');
    const objFontSize = document.getElementById('obj-fontsize');
    const objFontFamily = document.getElementById('obj-fontfamily');
    const objOpacity = document.getElementById('obj-opacity');
    const delBtn = document.getElementById('delete-obj-btn');
    const textGroupWrapper = document.getElementById('prop-text-group');

    function updateProps(obj) {
        if (!obj) {
            propertiesPanel.style.display = 'block';
            objectControls.classList.add('hidden');
            return;
        }

        propertiesPanel.style.display = 'none';
        objectControls.classList.remove('hidden');

        // Common props
        const fill = obj.get('fill') || '#000000';
        objColor.value = (typeof fill === 'string') ? fill : '#000000'; // Simple color support
        objColorHex.innerText = objColor.value;
        objOpacity.value = obj.get('opacity');

        // Text props
        if (obj.type === 'i-text' || obj.type === 'text') {
            textGroupWrapper.classList.remove('hidden');
            objFontSize.value = obj.get('fontSize');
            objFontFamily.value = obj.get('fontFamily');
        } else {
            textGroupWrapper.classList.add('hidden');
        }
    }

    // Listeners for changes
    objColor.addEventListener('input', () => {
        const obj = canvas.getActiveObject();
        if (obj) {
            obj.set('fill', objColor.value);
            objColorHex.innerText = objColor.value;
            canvas.requestRenderAll();
        }
    });

    objFontSize.addEventListener('change', () => {
        const obj = canvas.getActiveObject();
        if (obj) { obj.set('fontSize', parseInt(objFontSize.value)); canvas.requestRenderAll(); }
    });

    objFontFamily.addEventListener('change', () => {
        const obj = canvas.getActiveObject();
        if (obj) { obj.set('fontFamily', objFontFamily.value); canvas.requestRenderAll(); }
    });

    objOpacity.addEventListener('input', () => {
        const obj = canvas.getActiveObject();
        if (obj) { obj.set('opacity', parseFloat(objOpacity.value)); canvas.requestRenderAll(); }
    });

    delBtn.addEventListener('click', () => {
        const obj = canvas.getActiveObject();
        if (obj) {
            showGenericConfirm({
                title: 'Delete Element',
                message: 'Are you sure you want to remove this element?',
                confirmText: 'Delete',
                onConfirm: () => canvas.remove(obj)
            });
        }
    });


    // --- Adding Objects ---
    document.getElementById('add-text-btn').addEventListener('click', () => {
        const text = new fabric.IText('Text', {
            left: canvas.getCenter().left, top: canvas.getCenter().top,
            fontFamily: 'Arial', fill: '#333333', fontSize: 40,
            originX: 'center', originY: 'center'
        });
        canvas.add(text);
        canvas.setActiveObject(text);
    });

    const triggerUploadBtn = document.getElementById('trigger-image-upload');
    const fileInput = document.getElementById('image-upload-input');

    triggerUploadBtn.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', async (e) => {
        const file = e.target.files[0];
        if (!file) return;
        const formData = new FormData();
        formData.append('image', file);
        try {
            triggerUploadBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
            const res = await fetch('/api/events/upload_cert_asset.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                fabric.Image.fromURL(data.url, (img) => {
                    // Scale nicely
                    const maxSize = 300;
                    if (img.width > maxSize) img.scaleToWidth(maxSize);
                    img.set({ left: canvas.getCenter().left, top: canvas.getCenter().top, originX: 'center', originY: 'center' });
                    canvas.add(img);
                    canvas.setActiveObject(img);
                    showToast('Image uploaded successfully');
                }, { crossOrigin: 'anonymous' });
            } else {
                showToast(data.message, true);
            }
        } catch (e) {
            console.error(e);
            showToast('Upload failed', true);
        }
        finally {
            triggerUploadBtn.innerHTML = '<i class="fa-regular fa-image text-lg text-slate-700"></i><span class="text-[10px] text-slate-500">Image</span>';
            fileInput.value = '';
        }
    });

    document.querySelectorAll('.add-shape-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const type = btn.dataset.type;
            const center = canvas.getCenter();
            let shape;
            const opts = { left: center.left, top: center.top, fill: '#64748b', originX: 'center', originY: 'center' };

            if (type === 'rect') shape = new fabric.Rect({ ...opts, width: 100, height: 75 });
            if (type === 'circle') shape = new fabric.Circle({ ...opts, radius: 40 });
            if (type === 'triangle') shape = new fabric.Triangle({ ...opts, width: 80, height: 80 });
            if (type === 'line') shape = new fabric.Rect({ ...opts, width: 150, height: 4, fill: '#000' });

            if (shape) { canvas.add(shape); canvas.setActiveObject(shape); }
        });
    });

    document.getElementById('add-placeholder-btn').addEventListener('click', () => {
        const val = document.getElementById('placeholder-select').value;
        const text = new fabric.IText(val, {
            left: canvas.getCenter().left, top: canvas.getCenter().top - 50,
            fontFamily: 'Courier New', fill: '#4f46e5', fontSize: 32,
            textAlign: 'center', originX: 'center', originY: 'center'
        });
        canvas.add(text);
        canvas.setActiveObject(text);
    });

    // Save/Load/Clear
    document.getElementById('save-design-btn').addEventListener('click', () => {
        const json = JSON.stringify(canvas.toJSON());
        localStorage.setItem('cert_design_' + eventId, json);
        showToast('Design saved locally.');
    });
    document.getElementById('load-design-btn').addEventListener('click', () => {
        const json = localStorage.getItem('cert_design_' + eventId);
        if (json) {
            canvas.loadFromJSON(json, () => {
                canvas.renderAll();
                updateLayerList();
                setCanvasSizeFromCM(); // restore sizing visually if needed? fabric json doesn't imply resizing container
                showToast('Design loaded.');
            });
        } else {
            showToast('No saved design found.', true);
        }
    });
    document.getElementById('clear-canvas-btn').addEventListener('click', () => {
        showGenericConfirm({
            title: 'Clear Canvas',
            message: 'Are you sure you want to clear the entire design? This cannot be undone.',
            confirmText: 'Clear All',
            onConfirm: () => {
                canvas.clear();
                showToast('Canvas cleared.');
            }
        });
    });

    // --- Attendees & Sending ---
    let allAttendees = [];

    async function fetchAttendees() {
        const loadingFunc = document.getElementById('loading-attendees');
        try {
            const response = await fetch(`/api/events/get_certification_attendees.php?event_id=${eventId}`);
            const data = await response.json();
            if (data.success) {
                allAttendees = data.attendees;
                renderAttendeeTable(allAttendees);
            }
        } catch (error) { console.error(error); }
        finally { if (loadingFunc) loadingFunc.remove(); }
    }

    function renderAttendeeTable(list) {
        const tbody = document.getElementById('attendee-table-body');
        tbody.innerHTML = '';
        if (list.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="p-4 text-center text-slate-500">No attendees found.</td></tr>';
            return;
        }
        list.forEach(att => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-slate-50';
            tr.innerHTML = `
                <td class="py-3 pl-4 pr-3 text-sm"><input type="checkbox" class="attendee-checkbox w-4 h-4 text-indigo-600 rounded border-gray-300" 
                    value="${att.user_id}" 
                    data-email="${att.user_email}" 
                    data-name="${att.user_name}" 
                    data-code="${att.ticket_code}" 
                    data-company="${att.company_name || ''}"></td>
                <td class="px-3 py-3 text-sm text-slate-700 font-medium">${att.user_name}</td>
                <td class="px-3 py-3 text-sm text-slate-500">${att.user_email}</td>
                <td class="px-3 py-3 text-sm text-slate-500">${att.company_name || '-'}</td>
                <td class="px-3 py-3 text-sm text-slate-500"><span class="px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-xs font-semibold">Paid</span></td>
            `;
            tbody.appendChild(tr);
        });
        updateCount();
    }

    function updateCount() {
        document.getElementById('selected-count').innerText = document.querySelectorAll('.attendee-checkbox:checked').length;
    }

    document.getElementById('attendee-search').addEventListener('input', (e) => {
        const val = e.target.value.toLowerCase();
        const filtered = allAttendees.filter(a => a.user_name.toLowerCase().includes(val) || a.user_email.toLowerCase().includes(val));
        renderAttendeeTable(filtered);
    });

    document.getElementById('master-checkbox').addEventListener('change', (e) => {
        document.querySelectorAll('.attendee-checkbox').forEach(cb => cb.checked = e.target.checked);
        updateCount();
    });
    document.getElementById('attendee-table-body').addEventListener('change', updateCount);

    // --- SEND (With Zoom Reset) ---
    const processBatchSend = async (selected) => {
        const sendBtn = document.getElementById('send-certs-btn');
        const origText = sendBtn.innerHTML;
        sendBtn.disabled = true;
        sendBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Sending...';

        // Reset Zoom High Quality
        const curZoom = canvas.getZoom();
        const curW = canvas.getWidth();
        const curH = canvas.getHeight();

        canvas.setZoom(1);
        canvas.setWidth(parseFloat(widthInput.value) * PX_PER_CM);
        canvas.setHeight(parseFloat(heightInput.value) * PX_PER_CM);

        const baseJSON = canvas.toJSON();
        let sent = 0;
        let errors = 0;

        try {
            // Create modal logic here or simple alert loop?
            // Reusing the modal from previous step was good, but I removed it from view_file logic to save space?
            // Wait, I replaced the view content. I should check if I deleted the modal.
            // I did not explicitly delete the modal div in the replace_file_content if it was outside.
            // The previous modal was at the bottom.
            // I will assume simple sending logic here for now, or just log to console/alert.
            // Actually, the previous VIEW code had the modal at the very bottom.
            // My replacement targeted only the grid area. So modal MIGHT still be there.
            // But my replace instruction was "Replace the entire content of the `div class="mt-8"` block".

            for (let i = 0; i < selected.length; i++) {
                const cb = selected[i];
                const user = {
                    name: cb.dataset.name, email: cb.dataset.email,
                    company: cb.dataset.company, code: cb.dataset.code,
                    date: new Date().toLocaleDateString()
                };

                // Restore & Modify
                await new Promise(r => canvas.loadFromJSON(baseJSON, r));
                canvas.getObjects().forEach(obj => {
                    if ((obj.type === 'i-text' || obj.type === 'text') && obj.text.includes('{{')) {
                        obj.text = obj.text.replace('{{user_name}}', user.name)
                            .replace('{{company_name}}', user.company || '')
                            .replace('{{ticket_code}}', user.code || '')
                            .replace('{{date}}', user.date);
                    }
                });
                canvas.renderAll();

                const dataURL = canvas.toDataURL({ format: 'png', multiplier: 2.0 });

                const res = await fetch('/api/events/send_certificate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        event_id: eventId,
                        user_email: user.email,
                        user_name: user.name,
                        image_data: dataURL
                    })
                });

                if (res.ok) sent++;
                else errors++;

                sendBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin mr-2"></i> Sending... (${sent}/${selected.length})`;
            }

            if (errors > 0) showToast(`Finished with ${errors} errors. Sent ${sent}.`, true);
            else showToast(`Successfully sent ${sent} certificates!`);

        } catch (e) {
            console.error(e);
            showToast('Error during sending functionality', true);
        } finally {
            sendBtn.disabled = false;
            sendBtn.innerHTML = origText;
            // Restore
            canvas.setZoom(curZoom);
            canvas.setWidth(curW);
            canvas.setHeight(curH);
            canvas.loadFromJSON(baseJSON, canvas.renderAll.bind(canvas));
        }
    };

    document.getElementById('send-certs-btn').onclick = () => {
        const selected = Array.from(document.querySelectorAll('.attendee-checkbox:checked'));
        if (selected.length === 0) {
            showToast('Please select attendees first.', true);
            return;
        }

        showGenericConfirm({
            title: 'Send Certificates',
            message: `Are you sure you want to generate and email certificates to <strong>${selected.length}</strong> attendees?`,
            confirmText: 'Yes, Send All',
            onConfirm: () => processBatchSend(selected)
        });
    };

    // Initial fetch
    fetchAttendees();
});

