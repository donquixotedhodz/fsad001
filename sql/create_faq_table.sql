CREATE TABLE IF NOT EXISTS faq (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(100) NOT NULL,
    question VARCHAR(500) NOT NULL,
    answer TEXT NOT NULL,
    display_order INT NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_faq_question (question(191)),
    INDEX idx_faq_category (category),
    INDEX idx_faq_active_order (is_active, display_order)
);

INSERT INTO faq (category, question, answer, display_order, is_active) VALUES
('General', 'What is this FSAD system for?', 'The FSAD system is used to manage and monitor operational records such as MANAP documents, PPE transactions, AOM tracking, AD Scorecard entries, reports, and supporting maintenance data in one platform.', 1, 1),
('General', 'Who can access this system?', 'Access is role-based. Users must log in with an authorized account, and available pages depend on permissions set by administrators.', 2, 1),
('Navigation', 'How do I quickly access the modules I use most?', 'Use the Favorites feature to bookmark frequently accessed records or pages so you can open them faster from your dashboard or favorites list.', 3, 1),
('MANAP', 'How do I upload MANAP documents?', 'Open the MANAP page, provide the required metadata (such as EC, item, approvals, and authority), then upload the file. The system stores the document and related details for reporting and printing.', 4, 1),
('MANAP', 'Can one MANAP upload contain multiple items?', 'Yes. A single uploaded file can be associated with multiple items, recommending approvals, and approving authorities, depending on the encoded data.', 5, 1),
('PPE', 'How is PPE balance calculated?', 'The running balance is updated based on debit and credit entries. You can also use the recalculate function to refresh balances if needed after data corrections.', 6, 1),
('PPE', 'What PPE references are usually required when encoding?', 'Common references include date, particulars, check number, DV/OR number, debit amount, and credit amount. Complete and accurate values help ensure correct reporting.', 7, 1),
('PPE', 'What is the Remaining Balance page used for?', 'It maintains the current PPE fund balance (for example, PPE Provident Fund) and keeps a history of updates through audit logs.', 8, 1),
('AOM', 'What can I do in the AOM module?', 'You can encode and manage AOM records, filter and review entries, and generate printable or exportable outputs for submission and analysis.', 9, 1),
('AD Scorecard', 'What is the AD Scorecard module used for?', 'The AD Scorecard module captures scorecard-related records and supports reporting and print views for monitoring and evaluation.', 10, 1),
('Reports', 'Can I print and export reports?', 'Yes. The system provides print pages and export functions for several modules, including AOM, PPE, MANAP, and AD Scorecard.', 11, 1),
('Documents', 'How do I preview uploaded files?', 'Use the Documents or preview page to open uploaded files directly in the browser before downloading or printing.', 12, 1),
('Audit Logs', 'What actions are tracked in audit logs?', 'Major create, update, and other key data actions are logged with user details and timestamps to support traceability and accountability.', 13, 1),
('Maintenance', 'What can be configured in Maintenance?', 'Administrators can manage master data such as users, departments, electric cooperatives, and settings that support core transactions.', 14, 1),
('Troubleshooting', 'Why are my changes not visible in reports immediately?', 'Check your filters, date ranges, and saved records first. If balances look incorrect, run recalculation tools where applicable and reload the report.', 15, 1)
ON DUPLICATE KEY UPDATE
    category = VALUES(category),
    answer = VALUES(answer),
    display_order = VALUES(display_order),
    is_active = VALUES(is_active),
    updated_at = CURRENT_TIMESTAMP;
