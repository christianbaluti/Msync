<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Subscription</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen font-sans text-gray-900">

    <nav class="bg-white border-b border-gray-200 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <img class="h-14 w-auto" src="/assets/files/logo.png" alt="MemberSync Logo" />
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-6xl mx-auto px-4 py-10 sm:px-6 lg:px-8">
        
        <div id="loading" class="text-center py-20">
            <svg class="animate-spin h-10 w-10 text-indigo-600 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="mt-4 text-gray-500">Verifying your invitation...</p>
        </div>

        <div id="alreadyDoneState" class="hidden bg-white border border-green-200 rounded-xl p-10 text-center max-w-lg mx-auto mt-10 shadow-sm">
            <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-green-50 mb-6">
                <svg class="h-10 w-10 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900">Subscription Active</h3>
            <p class="mt-2 text-gray-600">You have already completed this subscription.</p>
        </div>
        
        <div id="expiredState" class="hidden bg-white border border-red-200 rounded-xl p-10 text-center max-w-lg mx-auto mt-10 shadow-sm">
            <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-red-50 mb-6">
                <svg class="h-10 w-10 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900">Link Expired</h3>
            <p class="mt-2 text-gray-600">This invitation link has expired.</p>
        </div>

        <div id="errorState" class="hidden bg-white border border-red-200 rounded-xl p-8 text-center max-w-lg mx-auto shadow-sm">
            <h3 class="text-lg font-medium text-gray-900">Access Denied</h3>
            <p id="errorMessage" class="mt-2 text-sm text-red-600">Invalid link.</p>
        </div>

        <div id="content" class="hidden">
            <div class="text-center mb-12">
                <h1 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Choose your Membership</h1>
                <p class="mt-4 text-lg text-gray-600">
                    Welcome, <span id="userNameDisplay" class="font-semibold text-indigo-600"></span>. <br>
                    Select a plan to generate your invoice and complete registration.
                </p>
            </div>
            <div id="membershipGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8"></div>
        </div>
    </main>

    <div id="paymentModal" class="relative z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-600 bg-opacity-75 transition-opacity backdrop-blur-sm"></div>
        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                
                <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-2xl border-t-8 border-indigo-600">
                    
                    <div id="step-address" class="px-8 py-8">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Billing Details</h3>
                        <p class="text-gray-500 mb-6">Please enter your physical or postal address for the invoice.</p>
                        
                        <div class="mb-6">
                            <label for="userAddressInput" class="block text-sm font-medium leading-6 text-gray-900">Address / Location</label>
                            <div class="mt-2">
                                <textarea id="userAddressInput" rows="3" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" placeholder="e.g. Plot 123, Chilambula Road, Lilongwe"></textarea>
                            </div>
                            <p id="addressError" class="mt-2 text-sm text-red-600 hidden">Address is required to generate an invoice.</p>
                        </div>

                        <div class="flex justify-end gap-3">
                            <button type="button" id="cancelAddressBtn" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Cancel</button>
                            <button type="button" id="nextToInvoiceBtn" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Generate Invoice &rarr;</button>
                        </div>
                    </div>

                    <div id="step-invoice" class="hidden">
                        
                        <div id="invoice-content" class="bg-gray-50 px-8 py-6">
                            <div class="border-b border-gray-200 pb-4 flex justify-between items-start mb-6">
                                <div>
                                    <img class="h-10 w-auto mb-2" src="/assets/files/logo.png" alt="Logo" />
                                </div>
                                <div class="text-right">
                                    <h2 class="text-3xl font-bold text-gray-300 tracking-widest">INVOICE</h2>
                                    <p class="text-sm text-gray-500 mt-1">Date: <span id="invoiceDate" class="font-medium text-gray-900"></span></p>
                                    <p class="text-sm text-gray-500">Ref: <span class="font-mono text-gray-900">PREVIEW</span></p>
                                </div>
                            </div>

                            <div class="flex justify-between mb-8">
                                <div class="w-1/2 pr-4">
                                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Billed To:</p>
                                    <p id="billToName" class="text-lg font-bold text-gray-900"></p>
                                    <p id="billToEmail" class="text-sm text-gray-600 mb-1"></p>
                                    <p id="billToAddress" class="text-sm text-gray-600 whitespace-pre-wrap"></p>
                                </div>
                                <div class="w-1/2 text-right pl-4">
                                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Pay To:</p>
                                    <p id="payToName" class="text-lg font-bold text-gray-900">MyIMM</p>
                                    <p id="payToAddress" class="text-sm text-gray-600 whitespace-pre-wrap"></p>
                                </div>
                            </div>

                            <div class="border rounded-lg overflow-hidden mb-6 bg-white">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <tr>
                                            <td class="px-6 py-4 text-sm text-gray-900">
                                                <span id="invoiceItemName" class="font-medium"></span> Membership
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900 text-right font-mono" id="invoiceItemPrice"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="flex justify-end mb-6">
                                <div class="w-full sm:w-1/2">
                                    <div class="flex justify-between py-2 border-t border-gray-200">
                                         <span class="text-sm text-gray-500">Subtotal</span>
                                         <span class="text-sm font-medium text-gray-900" id="invoiceSubtotal"></span>
                                    </div>
                                    <div class="flex justify-between py-3 border-t border-gray-200">
                                        <span class="text-base font-bold text-gray-900">Total Due</span>
                                        <span class="text-xl font-bold text-indigo-600" id="invoiceTotal"></span>
                                    </div>
                                </div>
                            </div>

                            <div id="pdfLinkContainer" class="hidden mt-8 pt-4 border-t border-gray-200">
                                <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Payment Link:</p>
                                <p class="text-xs text-blue-600 underline" id="pdfLinkDisplay" style="word-break: break-all; overflow-wrap: break-word;"></p>
                                <p class="text-xs text-gray-400 mt-1">Click the link above to proceed to payment.</p>
                            </div>
                        </div>

                        <div class="bg-white px-8 py-4 flex flex-col sm:flex-row-reverse sm:gap-3 border-t border-gray-200">
                            <button id="payButton" type="button" class="w-full inline-flex justify-center items-center rounded-md bg-indigo-600 px-6 py-3 text-sm font-bold text-white shadow-sm hover:bg-indigo-500 focus:outline-none sm:w-auto">
                                 <span id="payBtnText">Pay Now with Malipo</span>
                                 <svg id="paySpinner" class="animate-spin ml-2 h-4 w-4 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            </button>
                            
                            <button type="button" id="downloadPdfBtn" class="mt-3 sm:mt-0 inline-flex w-full justify-center items-center rounded-md bg-white px-4 py-3 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:w-auto">
                                <svg class="h-4 w-4 mr-2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Download PDF
                            </button>

                            <button type="button" id="backToAddressBtn" class="mt-3 sm:mt-0 inline-flex w-full justify-center rounded-md bg-white px-4 py-3 text-sm font-semibold text-gray-900 shadow-sm ring-0 hover:bg-gray-50 sm:w-auto text-gray-500">Back</button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

<script>
    // Extract Params
    const urlParams = new URLSearchParams(window.location.search);
    const uid = urlParams.get('uid');
    const token = urlParams.get('token');
    const currentUrl = window.location.href; 
    
    // State
    let selectedTypeId = null;
    let globalUserName = '';
    let globalUserEmail = '';
    let globalSystemName = '';
    let globalSystemAddress = '';
    let userAddress = ''; 
    
    // Elements
    const paymentModal = document.getElementById('paymentModal');
    const stepAddress = document.getElementById('step-address');
    const stepInvoice = document.getElementById('step-invoice');
    const addressInput = document.getElementById('userAddressInput');
    const addressError = document.getElementById('addressError');
    const pdfLinkDisplay = document.getElementById('pdfLinkDisplay');

    // Init
    document.addEventListener('DOMContentLoaded', async () => {
        pdfLinkDisplay.textContent = currentUrl; 

        if(!uid || !token) {
            showError("This link appears to be broken or incomplete.");
            return;
        }

        try {
            const response = await fetch(`/api/invoices/verify_access.php?uid=${uid}&token=${token}`);
            
            if (response.status === 403) {
                const errData = await response.json();
                if (errData.message && errData.message.includes('already been used')) {
                    showAlreadyDone();
                } else if (errData.message && errData.message.includes('expired')) {
                    showExpired();
                } else {
                    showError(errData.message);
                }
                return;
            }

            const data = await response.json();
            if (!data.success) {
                showError(data.message);
                return;
            }

            // Set Global Data
            globalUserName = data.user.name;
            globalUserEmail = data.user.email;
            globalSystemName = data.system.name;
            globalSystemAddress = data.system.address;

            document.getElementById('loading').classList.add('hidden');
            document.getElementById('content').classList.remove('hidden');
            document.getElementById('userNameDisplay').textContent = globalUserName;

            renderMemberships(data.membership_types);

        } catch (err) {
            showError("Could not connect to the server.");
        }
    });

    function showAlreadyDone() {
        document.getElementById('loading').classList.add('hidden');
        document.getElementById('alreadyDoneState').classList.remove('hidden');
    }
    
    function showExpired() {
        document.getElementById('loading').classList.add('hidden');
        document.getElementById('expiredState').classList.remove('hidden');
    }

    function showError(msg) {
        document.getElementById('loading').classList.add('hidden');
        document.getElementById('errorState').classList.remove('hidden');
        document.getElementById('errorMessage').textContent = msg;
    }

    function getMonthName(monthNumber) {
        const date = new Date();
        date.setMonth(monthNumber - 1);
        return date.toLocaleString('en-US', { month: 'long' });
    }

    function renderMemberships(types) {
        const grid = document.getElementById('membershipGrid');
        grid.innerHTML = '';

        types.forEach(type => {
            const feeFormatted = new Intl.NumberFormat('en-MW', { style: 'currency', currency: 'MWK' }).format(type.fee);
            
            let durationText = "Every Year";
            if (type.renewal_month && type.renewal_month >= 1 && type.renewal_month <= 12) {
                durationText = `Expires in ${getMonthName(type.renewal_month)} every year`;
            }

            const card = document.createElement('div');
            card.className = `relative flex flex-col rounded-2xl border border-gray-200 p-8 shadow-sm transition-all cursor-pointer hover:border-indigo-600 hover:shadow-xl bg-white group h-full`;
            card.onclick = () => startFlow(type.id, type.name, feeFormatted);

            card.innerHTML = `
                <div class="flex-1">
                    <h3 class="text-xl font-bold leading-8 text-gray-900 group-hover:text-indigo-600 transition-colors">${type.name}</h3>
                    <p class="mt-4 text-sm leading-6 text-gray-500">${type.description || 'Includes standard membership benefits.'}</p>
                </div>
                <div class="mt-8 pt-6 border-t border-gray-100">
                    <p class="flex items-baseline gap-x-1">
                        <span class="text-4xl font-bold tracking-tight text-gray-900">${feeFormatted}</span>
                    </p>
                    <p class="text-xs font-semibold leading-6 text-indigo-600 mt-2 uppercase tracking-wide bg-indigo-50 inline-block px-2 py-1 rounded">${durationText}</p>
                </div>
            `;
            grid.appendChild(card);
        });
    }

    // --- MODAL & FLOW LOGIC ---

    function startFlow(id, name, fee) {
        selectedTypeId = id;
        
        // Prepare Step 1
        addressInput.value = userAddress; 
        addressError.classList.add('hidden');
        
        // Prepare Step 2 (Invoice Data)
        document.getElementById('invoiceItemName').textContent = name;
        document.getElementById('invoiceItemPrice').textContent = fee;
        document.getElementById('invoiceSubtotal').textContent = fee; 
        document.getElementById('invoiceTotal').textContent = fee;
        
        document.getElementById('billToName').textContent = globalUserName;
        document.getElementById('billToEmail').textContent = globalUserEmail;
        
        document.getElementById('payToName').textContent = globalSystemName;
        document.getElementById('payToAddress').textContent = globalSystemAddress;

        const today = new Date().toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
        document.getElementById('invoiceDate').textContent = today;

        stepAddress.classList.remove('hidden');
        stepInvoice.classList.add('hidden');
        paymentModal.classList.remove('hidden');
    }

    // Step 1: Address Submit
    document.getElementById('nextToInvoiceBtn').addEventListener('click', () => {
        const val = addressInput.value.trim();
        if (!val) {
            addressError.classList.remove('hidden');
            return;
        }
        addressError.classList.add('hidden');
        userAddress = val;
        
        document.getElementById('billToAddress').textContent = userAddress;

        stepAddress.classList.add('hidden');
        stepInvoice.classList.remove('hidden');
    });

    document.getElementById('backToAddressBtn').addEventListener('click', () => {
        stepInvoice.classList.add('hidden');
        stepAddress.classList.remove('hidden');
    });

    const closeAll = () => {
        paymentModal.classList.add('hidden');
        selectedTypeId = null;
    };
    document.getElementById('cancelAddressBtn').addEventListener('click', closeAll);

    // --- DOWNLOAD PDF LOGIC (FIXED) ---
    document.getElementById('downloadPdfBtn').addEventListener('click', function() {
        const element = document.getElementById('invoice-content');
        const linkContainer = document.getElementById('pdfLinkContainer');

        // 1. Unhide BEFORE generation starts
        linkContainer.classList.remove('hidden');
        
        const opt = {
            margin:       0.5,
            filename:     'Invoice_Preview.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2 },
            jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
        };

        // 2. Generate PDF
        // Using explicit promise chain to ensure we wait for PDF generation to finish before hiding
        html2pdf().set(opt).from(element).save()
            .then(() => {
                // 3. Hide AFTER success
                linkContainer.classList.add('hidden');
            })
            .catch((err) => {
                // Hide even if error occurs
                console.error("PDF generation failed:", err);
                linkContainer.classList.add('hidden');
            });
    });

    // --- PAY LOGIC ---
    document.getElementById('payButton').addEventListener('click', async () => {
        if(!selectedTypeId) return;

        const btn = document.getElementById('payButton');
        const spinner = document.getElementById('paySpinner');
        const btnText = document.getElementById('payBtnText');

        btn.disabled = true;
        btnText.textContent = "Processing...";
        spinner.classList.remove('hidden');

        try {
            const response = await fetch('/api/invoices/public_process.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    uid: uid,
                    token: token,
                    membership_type_id: selectedTypeId,
                    email: globalUserEmail,
                    address: userAddress 
                })
            });

            const result = await response.json();

            if (result.success && result.redirect_url) {
                window.location.href = result.redirect_url;
            } else {
                alert(result.message || 'Payment initiation failed.');
                btn.disabled = false;
                btnText.textContent = "Pay Now with Malipo";
                spinner.classList.add('hidden');
            }

        } catch (error) {
            console.error(error);
            alert('A network error occurred.');
            btn.disabled = false;
            btnText.textContent = "Pay Now with Malipo";
            spinner.classList.add('hidden');
        }
    });
</script>
</body>
</html>