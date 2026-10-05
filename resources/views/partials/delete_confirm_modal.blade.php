<!-- Square 5-Second Delete Confirmation Popup Modal -->
<div id="deleteConfirmModal" class="square-delete-modal-overlay" style="display: none;">
    <div class="square-delete-backdrop" id="deleteModalBackdrop"></div>
    <div class="square-delete-card">
        <!-- Close button -->
        <button type="button" class="square-delete-close" id="deleteModalCloseBtn" title="Đóng">&times;</button>
        
        <!-- Animated Countdown Ring & Icon -->
        <div class="square-delete-icon-box">
            <svg class="square-delete-svg" viewBox="0 0 100 100">
                <circle class="square-delete-circle-bg" cx="50" cy="50" r="42"></circle>
                <circle class="square-delete-circle-bar" id="deleteCircleBar" cx="50" cy="50" r="42"></circle>
            </svg>
            <div class="square-delete-number" id="deleteCountdownNumber">5</div>
            <i class="fa-solid fa-trash-can square-delete-trash" id="deleteTrashIcon" style="display: none;"></i>
        </div>

        <!-- Title & Content -->
        <div class="square-delete-text-box">
            <h3 class="square-delete-title">Xác Nhận Xoá?</h3>
            <p class="square-delete-desc" id="deleteModalMessage">
                Hành động này không thể hoàn tác! Vui lòng chờ <span id="deleteTimerSpan" style="color: #ef4444; font-weight: 700;">5s</span> để xác nhận.
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="square-delete-actions">
            <button type="button" class="square-delete-btn cancel" id="deleteModalCancelBtn">
                Huỷ bỏ
            </button>
            <button type="button" class="square-delete-btn confirm disabled" id="deleteModalConfirmBtn" disabled>
                <span id="deleteBtnText">Chờ (5s)...</span>
            </button>
        </div>
    </div>
</div>

<style>
/* Square Delete Modal Styles */
.square-delete-modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 999999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
}

.square-delete-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(3, 7, 18, 0.82);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.square-delete-card {
    position: relative;
    z-index: 2;
    width: 360px;
    height: 360px;
    max-width: 92vw;
    max-height: 92vw;
    background: linear-gradient(145deg, #111827, #0b0f19);
    border: 2px solid rgba(239, 68, 68, 0.45);
    border-radius: 24px;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.95), 0 0 35px rgba(239, 68, 68, 0.25);
    padding: 24px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: space-between;
    text-align: center;
    box-sizing: border-box;
    animation: squareModalPop 0.28s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes squareModalPop {
    0% { opacity: 0; transform: scale(0.85); }
    100% { opacity: 1; transform: scale(1); }
}

.square-delete-close {
    position: absolute;
    top: 14px;
    right: 16px;
    background: transparent;
    border: none;
    color: #64748b;
    font-size: 1.5rem;
    line-height: 1;
    cursor: pointer;
    transition: color 0.15s ease;
    padding: 4px;
}

.square-delete-close:hover {
    color: #ef4444;
}

/* Icon & Countdown SVG */
.square-delete-icon-box {
    position: relative;
    width: 90px;
    height: 90px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-top: 4px;
}

.square-delete-svg {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    transform: rotate(-90deg);
}

.square-delete-circle-bg {
    fill: none;
    stroke: rgba(255, 255, 255, 0.08);
    stroke-width: 7;
}

.square-delete-circle-bar {
    fill: none;
    stroke: #ef4444;
    stroke-width: 7;
    stroke-linecap: round;
    stroke-dasharray: 264;
    stroke-dashoffset: 0;
    filter: drop-shadow(0 0 6px rgba(239, 68, 68, 0.8));
    transition: stroke-dashoffset 0.95s linear;
}

.square-delete-number {
    font-size: 2.2rem;
    font-weight: 900;
    color: #f87171;
    font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
    letter-spacing: -1px;
    animation: pulseNum 1s infinite alternate ease-in-out;
}

.square-delete-trash {
    font-size: 2.2rem;
    color: #ef4444;
    animation: trashShake 0.4s ease infinite alternate;
}

@keyframes pulseNum {
    0% { transform: scale(0.95); opacity: 0.9; }
    100% { transform: scale(1.05); opacity: 1; }
}

@keyframes trashShake {
    0% { transform: scale(1.05) rotate(-6deg); }
    100% { transform: scale(1.15) rotate(6deg); }
}

/* Text */
.square-delete-text-box {
    padding: 0 8px;
}

.square-delete-title {
    font-size: 1.18rem;
    font-weight: 800;
    color: #fff;
    margin-bottom: 6px;
    letter-spacing: -0.3px;
}

.square-delete-desc {
    font-size: 0.82rem;
    color: #94a3b8;
    line-height: 1.45;
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Actions */
.square-delete-actions {
    display: flex;
    gap: 12px;
    width: 100%;
}

.square-delete-btn {
    flex: 1;
    padding: 10px 14px;
    border-radius: 10px;
    font-size: 0.88rem;
    font-weight: 700;
    cursor: pointer;
    border: none;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.square-delete-btn.cancel {
    background: #1e293b;
    color: #cbd5e1;
    border: 1px solid #334155;
}

.square-delete-btn.cancel:hover {
    background: #334155;
    color: #fff;
}

.square-delete-btn.confirm.disabled {
    background: rgba(239, 68, 68, 0.15);
    color: #fca5a5;
    border: 1px solid rgba(239, 68, 68, 0.3);
    cursor: not-allowed;
    opacity: 0.7;
}

.square-delete-btn.confirm.active {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: #fff;
    border: 1px solid #ef4444;
    cursor: pointer;
    opacity: 1;
    box-shadow: 0 4px 18px rgba(239, 68, 68, 0.55);
    animation: confirmGlow 1.5s infinite alternate ease-in-out;
}

@keyframes confirmGlow {
    0% { transform: translateY(0); box-shadow: 0 4px 18px rgba(239, 68, 68, 0.55); }
    100% { transform: translateY(-1px); box-shadow: 0 6px 22px rgba(239, 68, 68, 0.8); }
}
</style>

<script>
(function() {
    let pendingForm = null;
    let countdownTimer = null;
    let remainingSeconds = 5;
    const TOTAL_SECONDS = 5;
    const CIRCUMFERENCE = 264;

    const modal = document.getElementById('deleteConfirmModal');
    const backdrop = document.getElementById('deleteModalBackdrop');
    const closeBtn = document.getElementById('deleteModalCloseBtn');
    const cancelBtn = document.getElementById('deleteModalCancelBtn');
    const confirmBtn = document.getElementById('deleteModalConfirmBtn');
    const numberEl = document.getElementById('deleteCountdownNumber');
    const trashIcon = document.getElementById('deleteTrashIcon');
    const circleBar = document.getElementById('deleteCircleBar');
    const btnText = document.getElementById('deleteBtnText');
    const msgEl = document.getElementById('deleteModalMessage');
    const timerSpan = document.getElementById('deleteTimerSpan');

    function openDeleteModal(form, message) {
        pendingForm = form;
        remainingSeconds = TOTAL_SECONDS;

        if (message) {
            msgEl.innerHTML = `${message}<br><span style="font-size: 0.76rem; color: #94a3b8;">Vui lòng chờ <span id="deleteTimerSpan" style="color: #ef4444; font-weight: 700;">5s</span> để mở khoá nút xoá.</span>`;
        } else {
            msgEl.innerHTML = `Hành động này sẽ xoá vĩnh viễn dữ liệu! Vui lòng chờ <span id="deleteTimerSpan" style="color: #ef4444; font-weight: 700;">5s</span> để xác nhận.`;
        }

        // Reset UI elements
        numberEl.style.display = 'block';
        numberEl.innerText = remainingSeconds;
        trashIcon.style.display = 'none';
        circleBar.style.strokeDashoffset = '0';
        confirmBtn.disabled = true;
        confirmBtn.classList.remove('active');
        confirmBtn.classList.add('disabled');
        btnText.innerText = `Chờ (${remainingSeconds}s)...`;

        modal.style.display = 'flex';

        // Start countdown interval
        clearInterval(countdownTimer);
        countdownTimer = setInterval(() => {
            remainingSeconds--;

            const timerSpanRef = document.getElementById('deleteTimerSpan');
            if (timerSpanRef) timerSpanRef.innerText = remainingSeconds + 's';

            if (remainingSeconds > 0) {
                numberEl.innerText = remainingSeconds;
                btnText.innerText = `Chờ (${remainingSeconds}s)...`;
                const offset = CIRCUMFERENCE - (remainingSeconds / TOTAL_SECONDS) * CIRCUMFERENCE;
                circleBar.style.strokeDashoffset = offset;
            } else {
                clearInterval(countdownTimer);
                circleBar.style.strokeDashoffset = CIRCUMFERENCE;
                numberEl.style.display = 'none';
                trashIcon.style.display = 'block';
                confirmBtn.disabled = false;
                confirmBtn.classList.remove('disabled');
                confirmBtn.classList.add('active');
                btnText.innerHTML = '<i class="fa-solid fa-trash-can"></i> Xác nhận xoá';
            }
        }, 1000);
    }

    function closeDeleteModal() {
        clearInterval(countdownTimer);
        modal.style.display = 'none';
        pendingForm = null;
    }

    if (backdrop) backdrop.addEventListener('click', closeDeleteModal);
    if (closeBtn) closeBtn.addEventListener('click', closeDeleteModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeDeleteModal);

    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            if (remainingSeconds <= 0 && pendingForm) {
                const formToSubmit = pendingForm;
                closeDeleteModal();
                formToSubmit._bypassDeleteModal = true;
                formToSubmit.submit();
            }
        });
    }

    // Escape key closes modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.style.display === 'flex') {
            closeDeleteModal();
        }
    });

    // Global interception for any delete form / button
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (!form || form._bypassDeleteModal) return;

        // Check if form is a DELETE request
        const methodInput = form.querySelector('input[name="_method"]');
        const isDeleteMethod = methodInput && methodInput.value.toUpperCase() === 'DELETE';
        const isDeleteAction = form.action && (form.action.includes('/destroy') || form.action.includes('/delete'));
        const hasConfirmAttr = form.hasAttribute('onsubmit') && form.getAttribute('onsubmit').includes('confirm');
        const hasDataConfirm = form.hasAttribute('data-confirm-delete');

        if (isDeleteMethod || isDeleteAction || hasDataConfirm || hasConfirmAttr) {
            e.preventDefault();
            e.stopImmediatePropagation();

            // Extract custom message from onsubmit if present
            let customMsg = null;
            if (form.hasAttribute('data-confirm-message')) {
                customMsg = form.getAttribute('data-confirm-message');
            } else if (hasConfirmAttr) {
                const match = form.getAttribute('onsubmit').match(/confirm\(['"]([^'"]+)['"]\)/);
                if (match && match[1]) {
                    customMsg = match[1];
                }
            }

            openDeleteModal(form, customMsg);
            return false;
        }
    }, true);
})();
</script>
