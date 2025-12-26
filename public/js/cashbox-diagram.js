/**
 * Cashbox Diagram - Tree visualization for money flow
 * Requirements: 9.1, 9.2, 9.3, 9.4, 9.5
 * 
 * Features:
 * - Tree-based visualization of money flow
 * - Role icons for each participant
 * - Status color coding
 * - Click to view transaction details
 * - Auto-refresh after transactions
 * - Animated connectors between nodes
 */
(function () {
    'use strict';

    // Configuration
    var CONFIG = {
        nodeWidth: 220,
        nodeHeight: 120,
        horizontalSpacing: 60,
        carryoverSpacing: 180,  // Extra spacing when there's carryover between deposits
        verticalSpacing: 60,
        connectorColor: '#dee2e6',
        connectorWidth: 2,
        animationDuration: 300,
        highlightColor: 'rgba(13, 110, 253, 0.25)',
        pulseAnimation: true
    };

    // Status colors
    var STATUS_COLORS = {
        pending: { bg: '#fff3cd', border: '#ffc107', badge: 'bg-warning' },
        in_progress: { bg: '#cff4fc', border: '#0dcaf0', badge: 'bg-info' },
        completed: { bg: '#d1e7dd', border: '#198754', badge: 'bg-success' },
        overdue: { bg: '#f8d7da', border: '#dc3545', badge: 'bg-danger' }
    };

    // Role colors
    var ROLE_COLORS = {
        boss: { bg: '#6f42c1', icon: 'ti ti-crown' },
        manager: { bg: '#0d6efd', icon: 'ti ti-user-star' },
        curator: { bg: '#20c997', icon: 'ti ti-user-check' },
        worker: { bg: '#6c757d', icon: 'ti ti-user' }
    };

    // Translations (will be overridden from PHP)
    var TRANSLATIONS = {
        deposit: 'Внесение',
        distribution: 'Выдача',
        refund: 'Возврат',
        self_salary: 'ЗП себе',
        pending: 'Ожидает',
        in_progress: 'В работе',
        completed: 'Выполнено',
        overdue: 'Просрочено',
        unknown: 'Неизвестно',
        loading: 'Загрузка...',
        error: 'Ошибка загрузки',
        no_transactions: 'Нет транзакций',
        sender: 'Отправитель',
        recipient: 'Получатель',
        amount: 'Сумма',
        status: 'Статус',
        task: 'Задача',
        comment: 'Комментарий',
        date: 'Дата',
        type: 'Тип операции',
        take_to_work: 'Взять в работу',
        close: 'Закрыть'
    };

    /**
     * CashboxDiagram class
     */
    function CashboxDiagram(container, options) {
        this.container = typeof container === 'string' ? document.getElementById(container) : container;
        this.options = Object.assign({}, CONFIG, options || {});
        this.data = null;
        this.svg = null;
        this.nodesContainer = null;
        this.selectedNode = null;

        // Currency symbol (default to € if not provided)
        this.currencySymbol = options.currencySymbol || '€';

        // Callbacks
        this.onNodeClick = options.onNodeClick || null;
        this.onStatusUpdate = options.onStatusUpdate || null;

        // Set translations if provided
        if (options.translations) {
            TRANSLATIONS = Object.assign(TRANSLATIONS, options.translations);
        }

        this.init();
    }

    CashboxDiagram.prototype.init = function () {
        if (!this.container) {
            console.error('CashboxDiagram: Container not found');
            return;
        }

        this.container.innerHTML = '';
        this.container.style.position = 'relative';
        this.container.style.overflow = 'auto';

        // Create SVG for connectors
        this.svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        this.svg.style.position = 'absolute';
        this.svg.style.top = '0';
        this.svg.style.left = '0';
        this.svg.style.pointerEvents = 'none';
        this.svg.style.zIndex = '1';
        this.container.appendChild(this.svg);

        // Create nodes container
        this.nodesContainer = document.createElement('div');
        this.nodesContainer.style.position = 'relative';
        this.nodesContainer.style.zIndex = '2';
        this.container.appendChild(this.nodesContainer);
    };

    CashboxDiagram.prototype.setData = function (data) {
        this.data = data;
        this.viewMode = data.view_mode || 'boss';
        this.render();
    };

    CashboxDiagram.prototype.getViewMode = function () {
        return this.viewMode || 'boss';
    };

    CashboxDiagram.prototype.render = function () {
        if (!this.data || !this.data.nodes || this.data.nodes.length === 0) {
            this.showEmpty();
            return;
        }

        this.nodesContainer.innerHTML = '';
        this.svg.innerHTML = '';

        // Calculate tree layout
        var layout = this.calculateLayout(this.data.nodes);

        // Set container size
        var maxX = 0, maxY = 0;
        layout.forEach(function (item) {
            maxX = Math.max(maxX, item.x + this.options.nodeWidth);
            maxY = Math.max(maxY, item.y + this.options.nodeHeight);
        }, this);

        this.nodesContainer.style.width = (maxX + 40) + 'px';
        this.nodesContainer.style.height = (maxY + 40) + 'px';
        this.svg.setAttribute('width', maxX + 40);
        this.svg.setAttribute('height', maxY + 40);

        // Draw carryover connectors between deposits first
        this.drawCarryoverConnectors(layout);

        // Draw connectors first
        layout.forEach(function (item) {
            if (item.parentX !== undefined && item.parentY !== undefined) {
                this.drawConnector(
                    item.parentX + this.options.nodeWidth / 2,
                    item.parentY + this.options.nodeHeight,
                    item.x + this.options.nodeWidth / 2,
                    item.y,
                    item.node.type === 'refund',
                    item.node,
                    layout
                );
            }
        }, this);

        // Draw nodes
        layout.forEach(function (item) {
            this.createNodeElement(item.node, item.x, item.y);
        }, this);
    };

    /**
     * Draw carryover connectors between child nodes (not deposits)
     * Shows remaining balance transferred from one transaction to the next
     */
    CashboxDiagram.prototype.drawCarryoverConnectors = function (layout) {
        var self = this;
        
        // Draw carryover arrows between child nodes of same recipient
        // Only for "transfer" type distributions (not salary)
        // Group nodes by recipient and level
        var nodesByRecipientAndLevel = {};
        layout.forEach(function (item) {
            // Only include transfer distributions (not salary) for carryover tracking
            if (item.node.type !== 'deposit' && 
                item.node.recipient && 
                item.node.distribution_type === 'transfer') {
                var key = item.node.recipient.type + '_' + item.node.recipient.id + '_level_' + (item.parentY || 0);
                if (!nodesByRecipientAndLevel[key]) {
                    nodesByRecipientAndLevel[key] = [];
                }
                nodesByRecipientAndLevel[key].push(item);
            }
        });
        
        // Draw carryover arrows between consecutive transfer nodes of same recipient
        // Use bottom path to avoid overlapping with other nodes
        Object.keys(nodesByRecipientAndLevel).forEach(function (key) {
            var nodes = nodesByRecipientAndLevel[key];
            // Sort by x position (left to right)
            nodes.sort(function (a, b) { return a.x - b.x; });
            
            for (var i = 0; i < nodes.length - 1; i++) {
                var currentNode = nodes[i];
                var nextNode = nodes[i + 1];
                
                // Check if there's a carryover amount
                if (currentNode.node.carryover_to_next && currentNode.node.carryover_to_next > 0) {
                    // Use bottom path for child nodes to avoid overlapping
                    self.drawCarryoverArrowBottom(
                        currentNode.x + self.options.nodeWidth / 2,
                        currentNode.y + self.options.nodeHeight,
                        nextNode.x + self.options.nodeWidth / 2,
                        nextNode.y + self.options.nodeHeight,
                        currentNode.node.carryover_to_next,
                        layout
                    );
                }
            }
        });
    };

    /**
     * Draw a carryover arrow with amount label
     */
    CashboxDiagram.prototype.drawCarryoverArrow = function (x1, y1, x2, y2, amount) {
        var self = this;

        // Create arrow path
        var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        var midX = x1 + (x2 - x1) / 2;

        var d = 'M ' + x1 + ' ' + y1 +
            ' C ' + midX + ' ' + y1 + ', ' + midX + ' ' + y2 + ', ' + x2 + ' ' + y2;

        path.setAttribute('d', d);
        path.setAttribute('fill', 'none');
        path.setAttribute('stroke', '#6c757d');
        path.setAttribute('stroke-width', '2');
        path.setAttribute('stroke-dasharray', '8,4');

        // Add arrow marker
        var markerId = 'carryover-arrow-' + Date.now() + Math.random();
        var defs = this.svg.querySelector('defs') || document.createElementNS('http://www.w3.org/2000/svg', 'defs');
        if (!this.svg.querySelector('defs')) {
            this.svg.appendChild(defs);
        }

        var marker = document.createElementNS('http://www.w3.org/2000/svg', 'marker');
        marker.setAttribute('id', markerId);
        marker.setAttribute('markerWidth', '10');
        marker.setAttribute('markerHeight', '7');
        marker.setAttribute('refX', '9');
        marker.setAttribute('refY', '3.5');
        marker.setAttribute('orient', 'auto');

        var polygon = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
        polygon.setAttribute('points', '0 0, 10 3.5, 0 7');
        polygon.setAttribute('fill', '#6c757d');

        marker.appendChild(polygon);
        defs.appendChild(marker);

        path.setAttribute('marker-end', 'url(#' + markerId + ')');

        this.svg.appendChild(path);

        // Add amount label above the arrow
        var labelX = midX;
        var labelY = Math.min(y1, y2) - 17;

        // Create background rect for label
        var labelBg = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        var labelText = '+' + self.formatMoney(amount) + ' ' + self.currencySymbol + ' (остаток)';
        var textWidth = labelText.length * 7;

        labelBg.setAttribute('x', labelX - textWidth / 2 - 6);
        labelBg.setAttribute('y', labelY - 12);
        labelBg.setAttribute('width', textWidth + 12);
        labelBg.setAttribute('height', 20);
        labelBg.setAttribute('rx', '4');
        labelBg.setAttribute('fill', '#f8f9fa');
        labelBg.setAttribute('stroke', '#6c757d');
        labelBg.setAttribute('stroke-width', '1');

        this.svg.appendChild(labelBg);

        // Create text label
        var text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        text.setAttribute('x', labelX);
        text.setAttribute('y', labelY + 2);
        text.setAttribute('text-anchor', 'middle');
        text.setAttribute('font-size', '11');
        text.setAttribute('font-weight', '600');
        text.setAttribute('fill', '#495057');
        text.textContent = labelText;

        this.svg.appendChild(text);
    };

    /**
     * Draw a carryover arrow that goes through the gap between node levels
     * Path: down from source -> horizontal in the gap -> up to target
     */
    CashboxDiagram.prototype.drawCarryoverArrowBottom = function (x1, y1, x2, y2, amount, layout) {
        var self = this;
        
        // Find the gap between the source node level and the next level
        // The horizontal line should go in the middle of the vertical spacing
        var gapY = y1 + (self.options.verticalSpacing / 2);
        
        // Create arrow path: down -> horizontal in gap -> up
        var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        
        // Path goes: start point -> down to gap -> horizontal -> up -> end point
        var d = 'M ' + x1 + ' ' + y1 +
            ' L ' + x1 + ' ' + gapY +
            ' L ' + x2 + ' ' + gapY +
            ' L ' + x2 + ' ' + y2;

        path.setAttribute('d', d);
        path.setAttribute('fill', 'none');
        path.setAttribute('stroke', '#6c757d');
        path.setAttribute('stroke-width', '2');
        path.setAttribute('stroke-dasharray', '8,4');

        // Add arrow marker
        var markerId = 'carryover-bottom-arrow-' + Date.now() + Math.random();
        var defs = this.svg.querySelector('defs') || document.createElementNS('http://www.w3.org/2000/svg', 'defs');
        if (!this.svg.querySelector('defs')) {
            this.svg.appendChild(defs);
        }

        var marker = document.createElementNS('http://www.w3.org/2000/svg', 'marker');
        marker.setAttribute('id', markerId);
        marker.setAttribute('markerWidth', '10');
        marker.setAttribute('markerHeight', '7');
        marker.setAttribute('refX', '9');
        marker.setAttribute('refY', '3.5');
        marker.setAttribute('orient', 'auto');

        var polygon = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
        polygon.setAttribute('points', '0 0, 10 3.5, 0 7');
        polygon.setAttribute('fill', '#6c757d');

        marker.appendChild(polygon);
        defs.appendChild(marker);

        path.setAttribute('marker-end', 'url(#' + markerId + ')');

        this.svg.appendChild(path);

        // Add amount label below the horizontal line
        var labelX = x1 + (x2 - x1) / 2;
        var labelY = gapY + 8;

        // Create background rect for label
        var labelBg = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        var labelText = '+' + self.formatMoney(amount) + ' ' + self.currencySymbol + ' (остаток)';
        var textWidth = labelText.length * 7;

        labelBg.setAttribute('x', labelX - textWidth / 2 - 6);
        labelBg.setAttribute('y', labelY);
        labelBg.setAttribute('width', textWidth + 12);
        labelBg.setAttribute('height', 20);
        labelBg.setAttribute('rx', '4');
        labelBg.setAttribute('fill', '#f8f9fa');
        labelBg.setAttribute('stroke', '#6c757d');
        labelBg.setAttribute('stroke-width', '1');

        this.svg.appendChild(labelBg);

        // Create text label
        var text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        text.setAttribute('x', labelX);
        text.setAttribute('y', labelY + 14);
        text.setAttribute('text-anchor', 'middle');
        text.setAttribute('font-size', '11');
        text.setAttribute('font-weight', '600');
        text.setAttribute('fill', '#495057');
        text.textContent = labelText;

        this.svg.appendChild(text);
    };

    CashboxDiagram.prototype.calculateLayout = function (nodes, level, parentX, parentY) {
        level = level || 0;
        var layout = [];
        var self = this;
        var startX = parentX !== undefined ? parentX : 20;
        var y = 20 + level * (this.options.nodeHeight + this.options.verticalSpacing);

        // Calculate total width needed for this level
        var totalWidth = 0;
        nodes.forEach(function (node, index) {
            var childrenWidth = self.getSubtreeWidth(node);
            totalWidth += childrenWidth;
            if (index < nodes.length - 1) {
                // Use extra spacing between nodes with carryover (deposits or child nodes)
                var spacing = self.options.horizontalSpacing;
                if (node.carryover_to_next && node.carryover_to_next > 0) {
                    spacing = self.options.carryoverSpacing;
                }
                totalWidth += spacing;
            }
        });

        // Center nodes under parent
        var currentX = startX;
        if (parentX !== undefined && totalWidth < this.options.nodeWidth) {
            currentX = parentX + (this.options.nodeWidth - totalWidth) / 2;
        } else if (parentX !== undefined) {
            currentX = parentX - (totalWidth - this.options.nodeWidth) / 2;
        }

        nodes.forEach(function (node, index) {
            var subtreeWidth = self.getSubtreeWidth(node);
            var nodeX = currentX + (subtreeWidth - self.options.nodeWidth) / 2;

            layout.push({
                node: node,
                x: Math.max(20, nodeX),
                y: y,
                parentX: parentX,
                parentY: parentY
            });

            // Process children
            var allChildren = (node.children || []).concat(node.refunds || []);
            if (allChildren.length > 0) {
                var childLayout = self.calculateLayout(
                    allChildren,
                    level + 1,
                    Math.max(20, nodeX),
                    y
                );
                layout = layout.concat(childLayout);
            }

            // Use extra spacing between nodes with carryover (deposits or child nodes)
            var spacing = self.options.horizontalSpacing;
            if (node.carryover_to_next && node.carryover_to_next > 0) {
                spacing = self.options.carryoverSpacing;
            }
            currentX += subtreeWidth + spacing;
        });

        return layout;
    };

    CashboxDiagram.prototype.getSubtreeWidth = function (node) {
        var self = this;
        var allChildren = (node.children || []).concat(node.refunds || []);

        if (allChildren.length === 0) {
            return this.options.nodeWidth;
        }

        var childrenWidth = 0;
        allChildren.forEach(function (child, index) {
            childrenWidth += self.getSubtreeWidth(child);
            if (index < allChildren.length - 1) {
                childrenWidth += self.options.horizontalSpacing;
            }
        });

        return Math.max(this.options.nodeWidth, childrenWidth);
    };

    CashboxDiagram.prototype.drawConnector = function (x1, y1, x2, y2, isRefund, node, layout) {
        var self = this;
        var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        var midY = y1 + (y2 - y1) / 2;

        var d = 'M ' + x1 + ' ' + y1 +
            ' C ' + x1 + ' ' + midY + ', ' + x2 + ' ' + midY + ', ' + x2 + ' ' + y2;

        path.setAttribute('d', d);
        path.setAttribute('fill', 'none');
        path.setAttribute('stroke', isRefund ? '#ffc107' : this.options.connectorColor);
        path.setAttribute('stroke-width', this.options.connectorWidth);

        if (isRefund) {
            path.setAttribute('stroke-dasharray', '5,5');
        }

        // Add arrow marker
        var markerId = 'arrow-' + (isRefund ? 'refund' : 'normal') + '-' + Date.now() + Math.random();
        var defs = this.svg.querySelector('defs') || document.createElementNS('http://www.w3.org/2000/svg', 'defs');
        if (!this.svg.querySelector('defs')) {
            this.svg.appendChild(defs);
        }

        var marker = document.createElementNS('http://www.w3.org/2000/svg', 'marker');
        marker.setAttribute('id', markerId);
        marker.setAttribute('markerWidth', '10');
        marker.setAttribute('markerHeight', '7');
        marker.setAttribute('refX', '9');
        marker.setAttribute('refY', '3.5');
        marker.setAttribute('orient', 'auto');

        var polygon = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
        polygon.setAttribute('points', '0 0, 10 3.5, 0 7');
        polygon.setAttribute('fill', isRefund ? '#ffc107' : this.options.connectorColor);

        marker.appendChild(polygon);
        defs.appendChild(marker);

        path.setAttribute('marker-end', 'url(#' + markerId + ')');

        this.svg.appendChild(path);
        
        // Add amount label for transfer distributions (not salary)
        // Position: centered above the target node (where arrow ends)
        if (node && node.type === 'distribution' && node.distribution_type === 'transfer' && node.original_amount) {
            var labelText = '+' + self.formatMoney(node.original_amount) + ' ' + self.currencySymbol;
            var textWidth = labelText.length * 6.5;
            var labelHeight = 20;
            
            // Position label centered above the target node (x2, y2 is top center of target node)
            var labelX = x2 - (textWidth + 10) / 2;
            var labelY = y2 - 25; // 25px above the node
            
            // Create background rect for label
            var labelBg = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
            labelBg.setAttribute('x', labelX);
            labelBg.setAttribute('y', labelY - 10);
            labelBg.setAttribute('width', textWidth + 10);
            labelBg.setAttribute('height', labelHeight);
            labelBg.setAttribute('rx', '3');
            labelBg.setAttribute('fill', '#ffffff');
            labelBg.setAttribute('stroke', '#dee2e6');
            labelBg.setAttribute('stroke-width', '1');
            this.svg.appendChild(labelBg);
            
            // Create text label
            var text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            text.setAttribute('x', labelX + 5);
            text.setAttribute('y', labelY + 4);
            text.setAttribute('font-size', '11');
            text.setAttribute('fill', '#495057');
            text.textContent = labelText;
            this.svg.appendChild(text);
        }
    };

    CashboxDiagram.prototype.createNodeElement = function (node, x, y) {
        var self = this;
        var statusColor = STATUS_COLORS[node.status] || STATUS_COLORS.pending;
        var recipient = node.recipient || {};
        var roleColor = ROLE_COLORS[recipient.role] || ROLE_COLORS.worker;

        var nodeEl = document.createElement('div');
        nodeEl.className = 'cashbox-node';
        nodeEl.setAttribute('data-node-id', node.id);
        nodeEl.style.cssText = [
            'position: absolute',
            'left: ' + x + 'px',
            'top: ' + y + 'px',
            'width: ' + this.options.nodeWidth + 'px',
            'min-height: ' + this.options.nodeHeight + 'px',
            'background: white',
            'border: 2px solid ' + statusColor.border,
            'border-radius: 8px',
            'padding: 12px',
            'box-shadow: 0 2px 8px rgba(0,0,0,0.1)',
            'cursor: pointer',
            'transition: transform 0.2s, box-shadow 0.2s'
        ].join(';');

        // Add refund styling
        if (node.type === 'refund') {
            nodeEl.style.borderStyle = 'dashed';
            nodeEl.style.background = '#fffbeb';
        }

        // Header with role icon and name
        var header = document.createElement('div');
        header.style.cssText = 'display: flex; align-items: center; margin-bottom: 8px;';

        var iconWrapper = document.createElement('div');
        iconWrapper.style.cssText = [
            'width: 32px',
            'height: 32px',
            'border-radius: 50%',
            'display: flex',
            'align-items: center',
            'justify-content: center',
            'margin-right: 8px',
            'background: ' + roleColor.bg,
            'color: white',
            'flex-shrink: 0'
        ].join(';');

        var icon = document.createElement('i');
        icon.className = recipient.icon || roleColor.icon;
        iconWrapper.appendChild(icon);

        var nameWrapper = document.createElement('div');
        nameWrapper.style.cssText = 'flex: 1; min-width: 0;';

        var name = document.createElement('div');
        name.style.cssText = 'font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;';
        name.textContent = recipient.name || TRANSLATIONS.unknown;
        name.title = recipient.name || '';

        var typeLabel = document.createElement('div');
        typeLabel.style.cssText = 'font-size: 11px; color: #6c757d;';
        var typeLabelText = TRANSLATIONS[node.type] || node.type;
        // Add distribution type label if present
        if (node.distribution_type_label) {
            typeLabelText += ' (' + node.distribution_type_label + ')';
        }
        typeLabel.textContent = typeLabelText;

        nameWrapper.appendChild(name);
        nameWrapper.appendChild(typeLabel);
        header.appendChild(iconWrapper);
        header.appendChild(nameWrapper);

        // Amount and status row
        var amountRow = document.createElement('div');
        amountRow.style.cssText = 'display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;';

        // Use current_balance if available, otherwise use amount
        var displayAmount = (node.current_balance !== undefined) ? node.current_balance : node.amount;
        var originalAmount = node.original_amount || node.amount;
        
        var amount = document.createElement('span');
        // Color based on whether money is spent or available
        var amountColor = displayAmount > 0 ? '#198754' : '#6c757d';
        amount.style.cssText = 'font-weight: 700; font-size: 16px; color: ' + amountColor + ';';
        amount.textContent = self.formatMoney(displayAmount) + ' ' + self.currencySymbol;
        
        // Show original amount as tooltip if different from current
        if (originalAmount !== displayAmount) {
            amount.title = 'Изначально: ' + self.formatMoney(originalAmount) + ' ' + self.currencySymbol;
        }

        var statusBadge = document.createElement('span');
        statusBadge.className = 'badge ' + statusColor.badge;
        statusBadge.style.cssText = 'font-size: 10px;';
        statusBadge.textContent = node.status_label || TRANSLATIONS[node.status] || node.status;

        amountRow.appendChild(amount);
        amountRow.appendChild(statusBadge);
        
        // Show original amount below if different (for transfer distributions)
        var originalAmountRow = null;
        if (originalAmount !== displayAmount && node.distribution_type === 'transfer') {
            originalAmountRow = document.createElement('div');
            originalAmountRow.style.cssText = 'font-size: 10px; color: #6c757d; margin-bottom: 4px;';
            originalAmountRow.innerHTML = '<i class="ti ti-arrow-down-right" style="margin-right: 2px;"></i>из ' + self.formatMoney(originalAmount) + ' ' + self.currencySymbol;
        }
        
        // Show carryover info at bottom right (for non-deposit nodes)
        var carryoverInfoRow = null;
        if (node.type !== 'deposit' && node.carryover_to_next && node.carryover_to_next > 0) {
            // Money transferred to next node
            carryoverInfoRow = document.createElement('div');
            carryoverInfoRow.style.cssText = 'font-size: 9px; color: #6c757d; text-align: right; margin-top: 4px;';
            carryoverInfoRow.innerHTML = '<i class="ti ti-arrow-right" style="margin-right: 2px;"></i>→ перенесено';
            carryoverInfoRow.title = 'Остаток ' + self.formatMoney(node.carryover_to_next) + ' ' + self.currencySymbol + ' перенесён в следующее перечисление';
        } else if (node.type !== 'deposit' && node.carryover_received && node.carryover_received > 0) {
            // Money received from previous node
            carryoverInfoRow = document.createElement('div');
            carryoverInfoRow.style.cssText = 'font-size: 9px; color: #198754; text-align: right; margin-top: 4px;';
            carryoverInfoRow.innerHTML = '<i class="ti ti-plus" style="margin-right: 2px;"></i>+ остаток';
            carryoverInfoRow.title = 'Получено ' + self.formatMoney(node.carryover_received) + ' ' + self.currencySymbol + ' из предыдущего перечисления';
        }
        
        // Show multiple deposits info for deposit nodes
        var multipleDepositsRow = null;
        if (node.type === 'deposit' && node.has_multiple_deposits && node.deposit_count > 1) {
            multipleDepositsRow = document.createElement('div');
            multipleDepositsRow.style.cssText = 'font-size: 9px; color: #198754; text-align: right; margin-top: 4px;';
            multipleDepositsRow.innerHTML = '<i class="ti ti-plus" style="margin-right: 2px;"></i>+внесение (' + node.deposit_count + ')';
            multipleDepositsRow.title = 'Всего ' + node.deposit_count + ' внесений';
        }

        // Task (if exists)
        var taskEl = null;
        if (node.task) {
            taskEl = document.createElement('div');
            taskEl.style.cssText = 'font-size: 11px; color: #6c757d; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;';
            taskEl.innerHTML = '<i class="ti ti-clipboard" style="margin-right: 4px;"></i>' + self.escapeHtml(node.task);
            taskEl.title = node.task;
        }

        // Assemble node
        nodeEl.appendChild(header);
        nodeEl.appendChild(amountRow);
        if (originalAmountRow) nodeEl.appendChild(originalAmountRow);
        if (taskEl) nodeEl.appendChild(taskEl);
        if (carryoverInfoRow) nodeEl.appendChild(carryoverInfoRow);
        if (multipleDepositsRow) nodeEl.appendChild(multipleDepositsRow);

        // Hover effects
        nodeEl.addEventListener('mouseenter', function () {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
        });

        nodeEl.addEventListener('mouseleave', function () {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
        });

        // Click handler
        nodeEl.addEventListener('click', function () {
            self.selectNode(node, nodeEl);
        });

        this.nodesContainer.appendChild(nodeEl);
        return nodeEl;
    };

    CashboxDiagram.prototype.selectNode = function (node, element) {
        // Remove previous selection
        var prevSelected = this.nodesContainer.querySelector('.cashbox-node-selected');
        if (prevSelected) {
            prevSelected.classList.remove('cashbox-node-selected');
            prevSelected.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
        }

        // Add selection to current
        element.classList.add('cashbox-node-selected');
        element.style.boxShadow = '0 0 0 3px rgba(13, 110, 253, 0.25), 0 4px 12px rgba(0,0,0,0.15)';

        this.selectedNode = node;

        if (this.onNodeClick) {
            this.onNodeClick(node);
        }
    };

    CashboxDiagram.prototype.showLoading = function () {
        this.nodesContainer.innerHTML = '<div style="text-align: center; padding: 60px 20px;">' +
            '<div class="spinner-border text-primary" role="status"></div>' +
            '<p style="margin-top: 12px; color: #6c757d;">' + TRANSLATIONS.loading + '</p>' +
            '</div>';
        this.svg.innerHTML = '';
    };

    CashboxDiagram.prototype.showEmpty = function () {
        this.nodesContainer.innerHTML = '<div style="text-align: center; padding: 60px 20px;">' +
            '<i class="ti ti-chart-dots" style="font-size: 48px; color: #ccc;"></i>' +
            '<p style="margin-top: 12px; color: #6c757d;">' + TRANSLATIONS.no_transactions + '</p>' +
            '</div>';
        this.svg.innerHTML = '';
    };

    CashboxDiagram.prototype.showError = function (message) {
        this.nodesContainer.innerHTML = '<div style="text-align: center; padding: 60px 20px;">' +
            '<i class="ti ti-alert-circle" style="font-size: 48px; color: #dc3545;"></i>' +
            '<p style="margin-top: 12px; color: #dc3545;">' + (message || TRANSLATIONS.error) + '</p>' +
            '</div>';
        this.svg.innerHTML = '';
    };

    CashboxDiagram.prototype.formatMoney = function (amount) {
        return parseFloat(amount).toLocaleString('ru-RU', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    };

    CashboxDiagram.prototype.escapeHtml = function (text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };

    CashboxDiagram.prototype.refresh = function () {
        if (this.data) {
            this.render();
        }
    };

    CashboxDiagram.prototype.getSelectedNode = function () {
        return this.selectedNode;
    };

    /**
     * Highlight a specific node by ID
     * Requirement 9.4: Update diagram on each new transaction
     */
    CashboxDiagram.prototype.highlightNode = function (nodeId) {
        var nodeEl = this.nodesContainer.querySelector('[data-node-id="' + nodeId + '"]');
        if (nodeEl) {
            // Add pulse animation
            nodeEl.style.animation = 'cashbox-pulse 0.5s ease-in-out 3';
            setTimeout(function () {
                nodeEl.style.animation = '';
            }, 1500);
        }
    };

    /**
     * Find a node by ID in the data tree
     */
    CashboxDiagram.prototype.findNodeById = function (nodeId, nodes) {
        nodes = nodes || (this.data ? this.data.nodes : []);
        for (var i = 0; i < nodes.length; i++) {
            if (nodes[i].id === nodeId) {
                return nodes[i];
            }
            // Search in children
            if (nodes[i].children && nodes[i].children.length > 0) {
                var found = this.findNodeById(nodeId, nodes[i].children);
                if (found) return found;
            }
            // Search in refunds
            if (nodes[i].refunds && nodes[i].refunds.length > 0) {
                var found = this.findNodeById(nodeId, nodes[i].refunds);
                if (found) return found;
            }
        }
        return null;
    };

    /**
     * Update a single node's status without full re-render
     * Requirement 9.4: Update diagram on each new transaction
     */
    CashboxDiagram.prototype.updateNodeStatus = function (nodeId, newStatus) {
        var node = this.findNodeById(nodeId);
        if (node) {
            node.status = newStatus;
            node.status_label = TRANSLATIONS[newStatus] || newStatus;
        }

        var nodeEl = this.nodesContainer.querySelector('[data-node-id="' + nodeId + '"]');
        if (nodeEl) {
            var statusColor = STATUS_COLORS[newStatus] || STATUS_COLORS.pending;
            nodeEl.style.borderColor = statusColor.border;

            var badge = nodeEl.querySelector('.badge');
            if (badge) {
                badge.className = 'badge ' + statusColor.badge;
                badge.style.fontSize = '10px';
                badge.textContent = TRANSLATIONS[newStatus] || newStatus;
            }

            // Highlight the updated node
            this.highlightNode(nodeId);
        }
    };

    /**
     * Scroll to a specific node
     */
    CashboxDiagram.prototype.scrollToNode = function (nodeId) {
        var nodeEl = this.nodesContainer.querySelector('[data-node-id="' + nodeId + '"]');
        if (nodeEl && this.container) {
            nodeEl.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
        }
    };

    /**
     * Get all nodes as flat array
     */
    CashboxDiagram.prototype.getAllNodes = function (nodes, result) {
        nodes = nodes || (this.data ? this.data.nodes : []);
        result = result || [];

        for (var i = 0; i < nodes.length; i++) {
            result.push(nodes[i]);
            if (nodes[i].children && nodes[i].children.length > 0) {
                this.getAllNodes(nodes[i].children, result);
            }
            if (nodes[i].refunds && nodes[i].refunds.length > 0) {
                this.getAllNodes(nodes[i].refunds, result);
            }
        }

        return result;
    };

    /**
     * Filter nodes by status
     */
    CashboxDiagram.prototype.filterByStatus = function (status) {
        var allNodeEls = this.nodesContainer.querySelectorAll('.cashbox-node');
        allNodeEls.forEach(function (el) {
            var nodeId = parseInt(el.getAttribute('data-node-id'));
            var node = this.findNodeById(nodeId);
            if (node) {
                if (status === 'all' || node.status === status) {
                    el.style.opacity = '1';
                    el.style.pointerEvents = 'auto';
                } else {
                    el.style.opacity = '0.3';
                    el.style.pointerEvents = 'none';
                }
            }
        }, this);
    };

    /**
     * Reset filter - show all nodes
     */
    CashboxDiagram.prototype.resetFilter = function () {
        this.filterByStatus('all');
    };

    /**
     * Add CSS animations to the page
     */
    CashboxDiagram.prototype.addStyles = function () {
        if (document.getElementById('cashbox-diagram-styles')) return;

        var style = document.createElement('style');
        style.id = 'cashbox-diagram-styles';
        style.textContent = `
            @keyframes cashbox-pulse {
                0% { transform: scale(1); box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
                50% { transform: scale(1.02); box-shadow: 0 4px 20px rgba(13, 110, 253, 0.3); }
                100% { transform: scale(1); box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
            }
            
            @keyframes cashbox-fade-in {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            .cashbox-node {
                animation: cashbox-fade-in 0.3s ease-out;
            }
            
            .cashbox-node-new {
                animation: cashbox-pulse 0.5s ease-in-out 2;
            }
        `;
        document.head.appendChild(style);
    };

    // Initialize styles when first diagram is created
    var originalInit = CashboxDiagram.prototype.init;
    CashboxDiagram.prototype.init = function () {
        this.addStyles();
        originalInit.call(this);
    };

    // Export to global scope
    window.CashboxDiagram = CashboxDiagram;
})();
