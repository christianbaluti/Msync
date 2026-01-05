<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive Raffle Draw</title>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    html, body {
        width: 100%;
        height: 100%;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
        background: white;
        color: #000;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    body {
        justify-content: center;
        align-items: center;
        line-height: 1.6;
    }

    /* Confetti & Fireworks stay above everything */
    .confetti,
    .firework {
        position: fixed;
        z-index: 1000;
    }

    /* Confetti animation */
    .confetti {
        width: 8px;
        height: 8px;
        animation: confetti-fall 3s linear forwards;
    }

    @keyframes confetti-fall {
        to {
            transform: translateY(100vh) rotate(360deg);
            opacity: 0;
        }
    }

    /* Firework animation */
    .firework {
        width: 3px;
        height: 3px;
        border-radius: 50%;
        animation: firework-burst 1s ease-out forwards;
    }

    @keyframes firework-burst {
        0% { transform: translate(0, 0); opacity: 1; }
        100% { transform: translate(var(--tx), var(--ty)); opacity: 0; }
    }

    /* Main layout */
    #game-container {
        flex: 1;
        width: 100%;
        max-width: 1200px;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        align-items: center;
        padding: 2vh 2vw;
        overflow-y: auto;
        scroll-behavior: smooth;
    }

    header {
        text-align: center;
        margin-bottom: 2vh;
    }

    h1 {
        font-size: clamp(1.8rem, 3vw, 2.5rem);
        font-weight: 300;
        letter-spacing: 2px;
        color: #000;
    }

    .subtitle {
        font-size: clamp(0.9rem, 1.2vw, 1rem);
        color: rgba(0, 0, 0, 0.5);
        font-weight: 300;
        letter-spacing: 1px;
    }

    /* Stats panel */
    #stats-panel {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        width: 100%;
        max-width: 900px;
        margin-bottom: 3vh;
    }

    .stat-card {
        background: #f5f5f5;
        padding: 1.5rem;
        border: 1px solid #e0e0e0;
        text-align: center;
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        border-color: #e40000;
        transform: translateY(-2px);
    }

    .stat-label {
        font-size: 0.85em;
        color: rgba(0, 0, 0, 0.5);
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 8px;
        font-weight: 500;
    }

    .stat-value {
        font-size: clamp(2rem, 3vw, 2.5rem);
        font-weight: 300;
        color: #e40000;
    }

    /* Main Raffle */
    #raffle-container {
        background: #fafafa;
        border: 1px solid #e0e0e0;
        padding: clamp(20px, 3vw, 50px);
        width: 100%;
        max-width: 900px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
    }

    #display-area {
        margin: 2vh 0;
        min-height: 150px;
        background: white;
        border: 2px solid #e40000;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        padding: 2vh 2vw;
        overflow: hidden;
    }

    #name-display {
        font-size: clamp(1.5rem, 2.5vw, 2.2rem);
        font-weight: 300;
        text-align: center;
        letter-spacing: 1px;
        color: #000;
    }

    #progress-container {
        width: 100%;
        height: 4px;
        background: #e0e0e0;
        margin: 2vh 0;
        overflow: hidden;
    }

    #progress-bar {
        height: 100%;
        background: #e40000;
        width: 0%;
        transition: width 0.1s linear;
    }

    .button-group {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: center;
        margin-top: 20px;
    }

    button {
        padding: 14px 40px;
        font-size: clamp(0.8rem, 1vw, 1rem);
        cursor: pointer;
        border: 1px solid;
        background: transparent;
        color: #000;
        transition: all 0.3s ease;
        text-transform: uppercase;
    }

    #draw-button {
        background: #e40000;
        border-color: #e40000;
        color: white;
    }

    #draw-button:hover:not(:disabled) {
        background: #ff0000;
        box-shadow: 0 0 20px rgba(228, 0, 0, 0.3);
    }

    #draw-button:disabled {
        background: #e0e0e0;
        border-color: #e0e0e0;
        color: #999;
        cursor: not-allowed;
    }

    .secondary-button {
        border-color: #e0e0e0;
    }

    .secondary-button:hover {
        border-color: #000;
        background: rgba(0, 0, 0, 0.05);
    }

    #winner-display {
        margin-top: 2vh;
        padding: 2vh;
        background: #fafafa;
        border: 2px solid #e40000;
        display: none;
    }

    #winner-name {
        font-size: clamp(1.8rem, 3vw, 3rem);
        color: #e40000;
        font-weight: 300;
        margin: 10px 0;
    }

    #winner-company {
        font-size: clamp(1rem, 1.5vw, 1.4rem);
        color: rgba(0, 0, 0, 0.7);
        font-weight: 300;
    }

    #history-container {
        margin-top: 3vh;
        width: 100%;
        max-width: 900px;
        background: #fafafa;
        border: 1px solid #e0e0e0;
        padding: clamp(20px, 2vw, 40px);
        flex-shrink: 0;
    }

    #history-list {
        max-height: 25vh;
        overflow-y: auto;
    }

    .history-item {
        padding: 1rem;
        margin: 10px 0;
        background: white;
        border: 1px solid #e0e0e0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.3s ease;
    }

    .history-item:hover {
        border-color: #e40000;
        background: #fafafa;
    }

    #control-bar {
        position: fixed;
        top: 20px;
        right: 20px;
        display: flex;
        gap: 10px;
        z-index: 100;
    }

    .icon-button {
        background: #f5f5f5;
        border: 1px solid #e0e0e0;
        padding: 12px;
        cursor: pointer;
        font-size: 1.2em;
        width: 45px;
        height: 45px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .icon-button:hover {
        border-color: #e40000;
        background: white;
    }

    @media (max-width: 768px) {
        #raffle-container {
            padding: 20px;
        }

        #history-list {
            max-height: 30vh;
        }

        h1 {
            font-size: 1.8rem;
        }

        #name-display {
            font-size: 1.4rem;
        }
    }
</style>

</head>
<body>
    <div id="control-bar">
        <button class="icon-button" id="sound-toggle" title="Toggle Sound">🔊</button>
    </div>

    <div id="game-container">
        <header>
            <h1>RAFFLE DRAW</h1>
            <p class="subtitle">Professional Event Winner Selection</p>
        </header>

        <!-- Stats Panel -->
        <div id="stats-panel">
            <div class="stat-card">
                <div class="stat-label">Eligible Participants</div>
                <div class="stat-value" id="participant-count">0</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Draws</div>
                <div class="stat-value" id="total-draws">0</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Status</div>
                <div class="stat-value" id="status-indicator" style="font-size: 1.2em;">●</div>
            </div>
        </div>

        <!-- Main Raffle -->
        <div id="raffle-container">
            <div id="display-area">
                <div id="name-display">Ready to Draw</div>
            </div>

            <div id="progress-container">
                <div id="progress-bar"></div>
            </div>

            <div class="button-group">
                <button id="draw-button" disabled>
                    <span id="button-text">Loading Participants</span>
                </button>
            </div>
            <div class="button-group" style="margin-top: 15px;">
                <button class="secondary-button" id="reload-button">Reload Data</button>
                <button class="secondary-button" id="clear-history-button">Clear History</button>
            </div>

            <div id="winner-display">
                <h2>Winner Announcement</h2>
                <div id="winner-name"></div>
                <div id="winner-company"></div>
            </div>
        </div>

        <!-- Winners History -->
        <div id="history-container">
            <h3>Winners Record</h3>
            <div id="history-list">
                <div class="empty-state">No winners yet. Click "Draw Winner" to begin.</div>
            </div>
        </div>
    </div>

    <script>
        const apiUrl = 'https://app.imm.mw/api/events/read_first_attendees.php';
        
        // DOM Elements
        const displayArea = document.getElementById('name-display');
        const drawButton = document.getElementById('draw-button');
        const buttonText = document.getElementById('button-text');
        const winnerDisplayDiv = document.getElementById('winner-display');
        const winnerNameSpan = document.getElementById('winner-name');
        const winnerCompanySpan = document.getElementById('winner-company');
        const progressBar = document.getElementById('progress-bar');
        const participantCountEl = document.getElementById('participant-count');
        const totalDrawsEl = document.getElementById('total-draws');
        const statusIndicator = document.getElementById('status-indicator');
        const historyList = document.getElementById('history-list');
        const soundToggle = document.getElementById('sound-toggle');
        const reloadButton = document.getElementById('reload-button');
        const clearHistoryButton = document.getElementById('clear-history-button');

        // Game State
        let attendees = [];
        let winners = [];
        let isDrawing = false;
        let totalDraws = 0;
        let soundEnabled = true;
        let audioContext = null;

        // Sound Effects
        function initAudio() {
            if (!audioContext) {
                audioContext = new (window.AudioContext || window.webkitAudioContext)();
            }
        }

        function playTone(frequency, duration) {
            if (!soundEnabled || !audioContext) return;
            
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.value = frequency;
            oscillator.type = 'sine';
            
            gainNode.gain.setValueAtTime(0.2, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + duration);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + duration);
        }

        function playSpinSound() {
            playTone(300 + Math.random() * 100, 0.03);
        }

        function playWinnerSound() {
            if (!soundEnabled || !audioContext) return;
            
            const notes = [523.25, 659.25, 783.99];
            notes.forEach((note, i) => {
                setTimeout(() => playTone(note, 0.3), i * 150);
            });
        }

        // Visual Effects
        function createConfetti() {
            const colors = ['#e40000', '#000000', '#999999'];
            for (let i = 0; i < 80; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    confetti.className = 'confetti';
                    confetti.style.left = Math.random() * 100 + 'vw';
                    confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
                    confetti.style.animationDelay = Math.random() * 0.3 + 's';
                    confetti.style.animationDuration = (Math.random() * 2 + 2) + 's';
                    document.body.appendChild(confetti);
                    
                    setTimeout(() => confetti.remove(), 3500);
                }, i * 25);
            }
        }

        function createFirework(x, y) {
            const colors = ['#e40000', '#000000'];
            for (let i = 0; i < 20; i++) {
                const firework = document.createElement('div');
                firework.className = 'firework';
                firework.style.left = x + 'px';
                firework.style.top = y + 'px';
                firework.style.background = colors[Math.floor(Math.random() * colors.length)];
                
                const angle = (Math.PI * 2 * i) / 20;
                const velocity = 40 + Math.random() * 40;
                firework.style.setProperty('--tx', Math.cos(angle) * velocity + 'px');
                firework.style.setProperty('--ty', Math.sin(angle) * velocity + 'px');
                
                document.body.appendChild(firework);
                setTimeout(() => firework.remove(), 1000);
            }
        }

        function launchFireworks() {
            for (let i = 0; i < 3; i++) {
                setTimeout(() => {
                    const x = Math.random() * window.innerWidth;
                    const y = Math.random() * window.innerHeight * 0.4;
                    createFirework(x, y);
                }, i * 400);
            }
        }

        // Update Status
        function updateStatus(status) {
            if (status === 'ready') {
                statusIndicator.style.color = '#00ff00';
            } else if (status === 'drawing') {
                statusIndicator.style.color = '#e40000';
            } else {
                statusIndicator.style.color = '#666';
            }
        }

        // Load Attendees
        async function loadAttendees() {
            drawButton.disabled = true;
            buttonText.textContent = 'Loading...';
            displayArea.innerHTML = '<div class="spinner"></div>';
            updateStatus('loading');
            
            try {
                const response = await fetch(apiUrl);
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                
                const data = await response.json();
                if (data.error) throw new Error(`API Error: ${data.error}`);
                
                if (data.attendees && data.attendees.length > 0) {
                    attendees = data.attendees.map(a => ({
                        full_name: a.full_name,
                        company_name: a.company_name || 'N/A'
                    }));
                    
                    participantCountEl.textContent = attendees.length;
                    drawButton.disabled = false;
                    buttonText.textContent = 'Draw Winner';
                    displayArea.textContent = 'Ready to Draw';
                    updateStatus('ready');
                } else {
                    participantCountEl.textContent = '0';
                    displayArea.textContent = 'No participants found';
                    buttonText.textContent = 'No Participants';
                    updateStatus('error');
                }
            } catch (error) {
                console.error('Error:', error);
                displayArea.textContent = `Error: ${error.message}`;
                buttonText.textContent = 'Reload Required';
                updateStatus('error');
            } finally {
                winnerDisplayDiv.style.display = 'none';
            }
        }

        // Raffle Draw Animation
        function startRaffle() {
            if (isDrawing || attendees.length === 0) return;
            
            initAudio();
            isDrawing = true;
            drawButton.disabled = true;
            buttonText.textContent = 'Drawing...';
            winnerDisplayDiv.style.display = 'none';
            progressBar.style.width = '0%';
            updateStatus('drawing');
            
            const spinDuration = 10000;
            const intervalTime = 50;
            const startTime = Date.now();
            
            const spinInterval = setInterval(() => {
                const elapsed = Date.now() - startTime;
                const progress = (elapsed / spinDuration) * 100;
                progressBar.style.width = progress + '%';
                
                const randomIndex = Math.floor(Math.random() * attendees.length);
                const randomAttendee = attendees[randomIndex];
                
                displayArea.innerHTML = `
                    <span class="attendee-name">${randomAttendee.full_name}</span>
                    <span class="company-name">${randomAttendee.company_name}</span>
                `;
                
                playSpinSound();
                
                if (elapsed >= spinDuration) {
                    clearInterval(spinInterval);
                    announceWinner();
                }
            }, intervalTime);
        }

        // Announce Winner
        function announceWinner() {
            const winnerIndex = Math.floor(Math.random() * attendees.length);
            const winner = attendees[winnerIndex];
            
            displayArea.innerHTML = `
                <span class="attendee-name">${winner.full_name}</span>
                <span class="company-name">${winner.company_name}</span>
            `;
            
            winnerNameSpan.textContent = winner.full_name;
            winnerCompanySpan.textContent = winner.company_name;
            winnerDisplayDiv.style.display = 'block';
            
            // Add to history
            totalDraws++;
            winners.unshift({ ...winner, timestamp: new Date() });
            updateHistory();
            totalDrawsEl.textContent = totalDraws;
            
            // Effects
            playWinnerSound();
            createConfetti();
            setTimeout(launchFireworks, 500);
            
            setTimeout(() => {
                isDrawing = false;
                drawButton.disabled = false;
                buttonText.textContent = 'Draw Winner';
                progressBar.style.width = '0%';
                updateStatus('ready');
            }, 1000);
        }

        // Update Winners History
        function updateHistory() {
            if (winners.length === 0) {
                historyList.innerHTML = '<div class="empty-state">No winners yet. Click "Draw Winner" to begin.</div>';
                return;
            }
            
            historyList.innerHTML = winners.map((winner, index) => `
                <div class="history-item">
                    <div style="display: flex; align-items: center;">
                        <span class="history-rank">${index + 1}</span>
                        <div class="history-content">
                            <span class="history-name">${winner.full_name}</span>
                            <span class="history-company">${winner.company_name}</span>
                        </div>
                    </div>
                    <div class="history-time">
                        ${winner.timestamp.toLocaleTimeString()}
                    </div>
                </div>
            `).join('');
        }

        // Event Listeners
        drawButton.addEventListener('click', startRaffle);
        
        reloadButton.addEventListener('click', () => {
            loadAttendees();
            playTone(440, 0.1);
        });
        
        clearHistoryButton.addEventListener('click', () => {
            if (confirm('Clear all winners history? This action cannot be undone.')) {
                winners = [];
                totalDraws = 0;
                updateHistory();
                totalDrawsEl.textContent = '0';
                playTone(330, 0.1);
            }
        });
        
        soundToggle.addEventListener('click', () => {
            soundEnabled = !soundEnabled;
            soundToggle.textContent = soundEnabled ? '🔊' : '🔇';
            playTone(440, 0.1);
        });

        // Initialize
        document.addEventListener('DOMContentLoaded', loadAttendees);
    </script>
</body>
</html>