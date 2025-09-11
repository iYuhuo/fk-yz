
class Modal {
    constructor() {
        this.createModalContainer();
    }

    createModalContainer() {
        if (document.getElementById('modal-container')) return;

        const container = document.createElement('div');
        container.id = 'modal-container';
        container.innerHTML = `
            <!-- 确认对话框 -->
            <div class="modal fade" id="confirmModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">确认操作</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p id="confirmMessage"></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                            <button type="button" class="btn btn-primary" id="confirmOk">确定</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 输入对话框 -->
            <div class="modal fade" id="promptModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="promptTitle">输入信息</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p id="promptMessage"></p>
                            <input type="text" class="form-control" id="promptInput" placeholder="">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                            <button type="button" class="btn btn-primary" id="promptOk">确定</button>
                        </div>
                    </div>
                </div>
            </div>



            <!-- 警告对话框 -->
            <div class="modal fade" id="alertModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="alertTitle">提示</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p id="alertMessage"></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">确定</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(container);
    }


    confirm(message, title = '确认操作') {
        return new Promise((resolve) => {
            const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
            document.getElementById('confirmMessage').textContent = message;
            document.querySelector('#confirmModal .modal-title').textContent = title;

            const okBtn = document.getElementById('confirmOk');
            const newOkBtn = okBtn.cloneNode(true);
            okBtn.parentNode.replaceChild(newOkBtn, okBtn);

            newOkBtn.addEventListener('click', () => {

                newOkBtn.blur();
                modal.hide();
                resolve(true);
            });

            document.getElementById('confirmModal').addEventListener('hidden.bs.modal', () => {

                if (document.activeElement) {
                    document.activeElement.blur();
                }
                resolve(false);
            }, { once: true });

            modal.show();
        });
    }


    prompt(message, defaultValue = '', title = '请输入') {
        return new Promise((resolve) => {
            const modal = new bootstrap.Modal(document.getElementById('promptModal'));
            const input = document.getElementById('promptInput');

            document.getElementById('promptMessage').textContent = message;
            document.getElementById('promptTitle').textContent = title;
            input.value = defaultValue;

            const okBtn = document.getElementById('promptOk');
            const newOkBtn = okBtn.cloneNode(true);
            okBtn.parentNode.replaceChild(newOkBtn, okBtn);

            newOkBtn.addEventListener('click', () => {
                const value = input.value.trim();
                newOkBtn.blur();
                input.blur();
                modal.hide();
                resolve(value || null);
            });

            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    const value = input.value.trim();
                    input.blur();
                    modal.hide();
                    resolve(value || null);
                }
            });

            document.getElementById('promptModal').addEventListener('hidden.bs.modal', () => {

                if (document.activeElement) {
                    document.activeElement.blur();
                }
                resolve(null);
            }, { once: true });

            document.getElementById('promptModal').addEventListener('shown.bs.modal', () => {
                input.focus();
                input.select();
            }, { once: true });

            modal.show();
        });
    }


    alert(message, title = '提示', type = 'info') {
        return new Promise((resolve) => {
            const modal = new bootstrap.Modal(document.getElementById('alertModal'));
            document.getElementById('alertMessage').textContent = message;
            document.getElementById('alertTitle').textContent = title;


            const modalContent = document.querySelector('#alertModal .modal-content');
            modalContent.className = 'modal-content';
            if (type === 'error') {
                modalContent.classList.add('border-danger');
                document.getElementById('alertTitle').className = 'modal-title text-danger';
            } else if (type === 'success') {
                modalContent.classList.add('border-success');
                document.getElementById('alertTitle').className = 'modal-title text-success';
            } else {
                document.getElementById('alertTitle').className = 'modal-title text-info';
            }

            document.getElementById('alertModal').addEventListener('hidden.bs.modal', () => {
                resolve();
            }, { once: true });

            modal.show();
        });
    }


    select(message, options = [], defaultValue = null, title = '请选择') {
        return new Promise((resolve) => {

            this.createSelectModalHTML();

            setTimeout(() => {
                const modalElement = document.getElementById('selectModal');
                if (!modalElement) {
                    console.error('Select modal element not found');
                    resolve(null);
                    return;
                }

                const modal = new bootstrap.Modal(modalElement);
                const messageElement = document.getElementById('selectMessage');
                const titleElement = document.getElementById('selectTitle');
                const optionsContainer = document.getElementById('selectOptions');

                if (!messageElement || !titleElement || !optionsContainer) {
                    console.error('Select modal elements not found');
                    resolve(null);
                    return;
                }


                titleElement.textContent = title;
                messageElement.textContent = message;


                optionsContainer.innerHTML = '';
                let selectedValue = defaultValue;

                options.forEach((option, index) => {
                    const optionDiv = document.createElement('div');
                    optionDiv.className = 'form-check';

                    const radioId = `selectOption${index}`;
                    const isChecked = option.value === defaultValue || (index === 0 && !defaultValue);
                    if (isChecked) selectedValue = option.value;

                    optionDiv.innerHTML = `
                        <input class="form-check-input" type="radio" name="selectOptions" id="${radioId}" value="${option.value}" ${isChecked ? 'checked' : ''}>
                        <label class="form-check-label" for="${radioId}">
                            ${option.label}
                        </label>
                    `;

                    optionsContainer.appendChild(optionDiv);


                    const radio = optionDiv.querySelector('input');
                    radio.addEventListener('change', () => {
                        if (radio.checked) {
                            selectedValue = radio.value;
                        }
                    });
                });

                const okBtn = document.getElementById('selectOk');
                const newOkBtn = okBtn.cloneNode(true);
                okBtn.parentNode.replaceChild(newOkBtn, okBtn);

                newOkBtn.addEventListener('click', () => {
                    newOkBtn.blur();
                    modal.hide();
                    resolve(selectedValue);
                });

                modalElement.addEventListener('hidden.bs.modal', () => {

                    if (modalElement.parentNode) {
                        modalElement.parentNode.removeChild(modalElement);
                    }
                    if (document.activeElement) {
                        document.activeElement.blur();
                    }
                    resolve(null);
                }, { once: true });

                modal.show();
            }, 10);
        });
    }


    createSelectModalHTML() {

        const existingModal = document.getElementById('selectModal');
        if (existingModal) {
            existingModal.remove();
        }

        const modalHTML = `
        <div class="modal fade" id="selectModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="selectTitle">请选择</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p id="selectMessage"></p>
                        <div id="selectOptions"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="button" class="btn btn-primary" id="selectOk">确定</button>
                    </div>
                </div>
            </div>
        </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }


    createLicenseDialog() {
        return new Promise((resolve) => {

            const existingModal = document.getElementById('createLicenseModal');
            if (existingModal) {
                existingModal.remove();
            }


            this.createLicenseModalHTML();


            setTimeout(() => {
                const modalElement = document.getElementById('createLicenseModal');

                if (!modalElement) {
                    console.error('Modal element not found');
                    resolve(null);
                    return;
                }

                const modal = new bootstrap.Modal(modalElement);
                const countInput = document.getElementById('licenseCount');
                const daysInput = document.getElementById('licenseDays');
                const prefixInput = document.getElementById('licensePrefix');
                const lengthInput = document.getElementById('licenseLength');
                const charsetSelect = document.getElementById('licenseCharset');
                const customFormatDiv = document.getElementById('customFormatDiv');
                const useDefaultCheckbox = document.getElementById('useDefaultFormat');


                if (!countInput || !daysInput) {
                    console.error('Basic modal elements not found');
                    resolve(null);
                    return;
                }


                countInput.value = '1';
                daysInput.value = '365';
                if (prefixInput) prefixInput.value = 'zz';
                if (lengthInput) lengthInput.value = '18';
                if (charsetSelect) charsetSelect.value = 'abcdefghijklmnopqrstuvwxyz0123456789';
                if (useDefaultCheckbox) useDefaultCheckbox.checked = true;
                if (customFormatDiv) customFormatDiv.style.display = 'none';


                if (useDefaultCheckbox && customFormatDiv) {
                    useDefaultCheckbox.addEventListener('change', (e) => {
                        customFormatDiv.style.display = e.target.checked ? 'none' : 'block';
                        updatePreview();
                    });
                }


                function updatePreview() {
                    const previewElement = document.getElementById('formatPreview');
                    if (!previewElement) return;

                    if (useDefaultCheckbox && useDefaultCheckbox.checked) {
                        previewElement.textContent = 'zz + 16位随机字符 (总长度18位)';
                    } else if (prefixInput && lengthInput && charsetSelect) {
                        const prefix = prefixInput.value || 'zz';
                        const totalLength = parseInt(lengthInput.value) || 18;
                        const randomLength = Math.max(0, totalLength - prefix.length);
                        const charsetName = charsetSelect.options[charsetSelect.selectedIndex].text;
                        previewElement.textContent = `${prefix} + ${randomLength}位随机字符 (${charsetName}, 总长度${totalLength}位)`;
                    }
                }


                if (prefixInput) prefixInput.addEventListener('input', updatePreview);
                if (lengthInput) lengthInput.addEventListener('input', updatePreview);
                if (charsetSelect) charsetSelect.addEventListener('change', updatePreview);


                updatePreview();

                const okBtn = document.getElementById('createLicenseOk');
                const newOkBtn = okBtn.cloneNode(true);
                okBtn.parentNode.replaceChild(newOkBtn, okBtn);

                newOkBtn.addEventListener('click', () => {
                const count = parseInt(countInput.value);
                const days = parseInt(daysInput.value);

                if (!count || !days || count < 1 || count > 100 || days < 1 || days > 3650) {
                    alert('输入的参数不正确，请检查数量和有效期范围');
                    return;
                }


                let formatOptions = null;
                const useDefaultCheckbox = document.getElementById('useDefaultFormat');
                const prefixInput = document.getElementById('licensePrefix');
                const lengthInput = document.getElementById('licenseLength');
                const charsetSelect = document.getElementById('licenseCharset');

                if (useDefaultCheckbox && !useDefaultCheckbox.checked && prefixInput && lengthInput && charsetSelect) {
                    const prefix = prefixInput.value.trim();
                    const length = parseInt(lengthInput.value);
                    const charset = charsetSelect.value;

                    if (!prefix || length < 8 || length > 64 || !charset) {
                        alert('自定义格式参数不正确，请检查前缀、长度和字符集');
                        return;
                    }

                    formatOptions = { prefix, length, charset };
                }

                    newOkBtn.blur();
                    countInput.blur();
                    daysInput.blur();
                    if (prefixInput) prefixInput.blur();
                    if (lengthInput) lengthInput.blur();
                    if (charsetSelect) charsetSelect.blur();
                    modal.hide();
                    resolve({ count, days, formatOptions });
                });

                document.getElementById('createLicenseModal').addEventListener('hidden.bs.modal', () => {

                    if (document.activeElement) {
                        document.activeElement.blur();
                    }
                    resolve(null);
                }, { once: true });

                modal.show();
            }, 10);
        });
    }


    createLicenseModalHTML() {

        const existingModal = document.getElementById('createLicenseModal');
        if (existingModal) {
            existingModal.remove();
        }
        const modalHTML = `
        <div class="modal fade" id="createLicenseModal" tabindex="-1" aria-labelledby="createLicenseModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="createLicenseModalLabel">
                            <i class="bi bi-plus-circle me-2"></i>创建许可证
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- 基本设置 -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="bi bi-gear me-2"></i>基本设置
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label for="licenseCount" class="form-label">
                                                <i class="bi bi-hash me-1"></i>数量
                                            </label>
                                            <input type="number" class="form-control" id="licenseCount" min="1" max="100" value="1">
                                            <div class="form-text">1-100个许可证</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label for="licenseDays" class="form-label">
                                                <i class="bi bi-calendar me-1"></i>有效期(天)
                                            </label>
                                            <input type="number" class="form-control" id="licenseDays" min="1" max="3650" value="365">
                                            <div class="form-text">1-3650天有效期</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 格式设置 -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="bi bi-code me-2"></i>格式设置
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="useDefaultFormat" checked>
                                        <label class="form-check-label" for="useDefaultFormat">
                                            <strong>使用默认格式</strong>
                                            <small class="text-muted d-block">格式：zz + 16位随机字符 (共18位)</small>
                                        </label>
                                    </div>
                                </div>

                                <div id="customFormatDiv" style="display: none;">
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i>
                                        <strong>自定义格式</strong> - 您可以自定义许可证的生成规则
                                    </div>
                                    <div class="row">
                                        <div class="col-4">
                                            <div class="mb-3">
                                                <label for="licensePrefix" class="form-label">
                                                    <i class="bi bi-type me-1"></i>前缀
                                                </label>
                                                <input type="text" class="form-control" id="licensePrefix" value="zz" placeholder="例如: zz, LIC-">
                                                <div class="form-text">许可证开头字符</div>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="mb-3">
                                                <label for="licenseLength" class="form-label">
                                                    <i class="bi bi-rulers me-1"></i>总长度
                                                </label>
                                                <input type="number" class="form-control" id="licenseLength" min="8" max="64" value="18">
                                                <div class="form-text">8-64个字符</div>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="mb-3">
                                                <label for="licenseCharset" class="form-label">
                                                    <i class="bi bi-alphabet me-1"></i>字符集
                                                </label>
                                                <select class="form-select" id="licenseCharset">
                                                    <option value="abcdefghijklmnopqrstuvwxyz0123456789">小写字母+数字</option>
                                                    <option value="ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789">大写字母+数字</option>
                                                    <option value="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789">大小写字母+数字</option>
                                                    <option value="0123456789">仅数字</option>
                                                    <option value="ABCDEFGHIJKLMNOPQRSTUVWXYZ">仅大写字母</option>
                                                    <option value="abcdefghijklmnopqrstuvwxyz">仅小写字母</option>
                                                </select>
                                                <div class="form-text">生成时使用的字符</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 预览 -->
                                    <div class="alert alert-secondary">
                                        <i class="bi bi-eye me-2"></i>
                                        <strong>示例：</strong>
                                        <code id="formatPreview">zz + 16位随机字符</code>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-1"></i>取消
                        </button>
                        <button type="button" class="btn btn-primary" id="createLicenseOk">
                            <i class="bi bi-plus-circle me-1"></i>创建许可证
                        </button>
                    </div>
                </div>
            </div>
        </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }
}


window.modernModal = new Modal();


window.confirm = (message) => window.modernModal.confirm(message);
window.prompt = (message, defaultValue) => window.modernModal.prompt(message, defaultValue);
window.alert = (message) => window.modernModal.alert(message);