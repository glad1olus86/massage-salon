<style>
.form-section {
    margin-top: 20px;
}
.form-content {
    padding: 30px;
}
.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}
.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.form-group--full {
    grid-column: 1 / -1;
}
.form-label {
    font-size: 14px;
    font-weight: 600;
    color: var(--accent-color);
}
.form-input,
.form-select,
.form-textarea {
    padding: 12px 16px;
    border: 2px solid rgba(177, 32, 84, 0.2);
    border-radius: 10px;
    font-size: 16px;
    transition: border-color 0.2s;
    background: #fff;
}
.form-input:focus,
.form-select:focus,
.form-textarea:focus {
    outline: none;
    border-color: var(--brand-color);
}
.form-select {
    cursor: pointer;
}
.form-textarea {
    resize: vertical;
    min-height: 100px;
}
.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 15px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid rgba(22, 11, 14, 0.1);
}
.btn {
    height: 44px;
    padding: 0 24px;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.2s;
}
.btn--dark {
    background: var(--accent-color);
    color: #fff;
    border: 2px solid var(--accent-color);
}
.btn--dark:hover {
    opacity: 0.9;
}
.btn--outlined-dark {
    background: transparent;
    color: var(--accent-color);
    border: 2px solid var(--accent-color);
}
.btn--outlined-dark:hover {
    background: rgba(22, 11, 14, 0.05);
}
@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    .form-content {
        padding: 20px;
    }
    .form-actions {
        flex-direction: column;
    }
    .btn {
        width: 100%;
    }
}
</style>
