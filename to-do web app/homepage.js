document.addEventListener('DOMContentLoaded', function() {
    let lists = [];
    let draggedCard = null, draggedList = null, dragOperation = null;
    let hasActiveBoard = false;

    renderBoard();
    setupModalHandlers();

    function renderBoard() {
        const board = document.getElementById('board');
        
        if (!hasActiveBoard) {
            board.innerHTML = `
                <div class="welcome-message">
                    <div class="welcome-icon">📋</div>
                    <h1>Welcome to Taskmaster!</h1>
                    <p>Organize your projects and boost productivity with beautiful, intuitive boards</p>
                    <button class="create-first-board">✨ Create Your First Board</button>
                </div>
            `;
            document.querySelector('.create-first-board').onclick = () => {
                document.querySelector('.add-board').click();
            };
            return;
        }

        board.innerHTML = '';
        lists.forEach(list => board.appendChild(createListElement(list)));
        
        const addListBtn = document.createElement('div');
        addListBtn.className = 'add-list';
        addListBtn.innerHTML = '<div class="add-list-icon">+</div> Add another list';
        addListBtn.onclick = () => {
            lists.push({ id: `list-${Date.now()}`, title: 'New List', color: '#4bce97', cards: [] });
            renderBoard();
        };
        board.appendChild(addListBtn);
        
        setupBoardTitleEdit();
        setupDragAndDrop();
    }

    function createListElement(list) {
        const listEl = document.createElement('div');
        listEl.className = 'list';
        listEl.id = list.id;
        listEl.draggable = true;
        
        listEl.innerHTML = `
            <div class="list-header">
                <div class="list-title">
                    <div class="list-color" style="background-color: ${list.color}"></div>
                    <div class="list-title-text">${list.title}</div>
                </div>
                <div class="delete-list">✕</div>
            </div>
            <div class="list-cards" data-list-id="${list.id}"></div>
            <div class="add-card">+ Add a card</div>
        `;

        const cardsContainer = listEl.querySelector('.list-cards');
        list.cards.forEach(card => cardsContainer.appendChild(createCardElement(card)));

        setupListHandlers(listEl, list);
        return listEl;
    }

    function createCardElement(card) {
        const cardEl = document.createElement('div');
        cardEl.className = 'card';
        cardEl.id = card.id;
        cardEl.draggable = true;
        cardEl.innerHTML = `
            <div class="card-color" style="background-color: ${card.color}"></div>
            <div class="card-content">${card.content}</div>
        `;
        
        setupCardHandlers(cardEl);
        return cardEl;
    }

    function setupListHandlers(listEl, list) {
        const titleText = listEl.querySelector('.list-title-text');
        const deleteBtn = listEl.querySelector('.delete-list');
        const addCard = listEl.querySelector('.add-card');

        titleText.onclick = (e) => setupInlineEdit(e.target, 'input', (newValue) => {
            list.title = newValue;
            titleText.textContent = newValue;
        });

        deleteBtn.onclick = (e) => {
            e.stopPropagation();
            const idx = lists.findIndex(l => l.id === list.id);
            if (idx !== -1) { lists.splice(idx, 1); renderBoard(); }
        };

        addCard.onclick = () => {
            list.cards.push({ id: `card-${Date.now()}`, content: 'New Card', color: list.color });
            renderBoard();
        };

        listEl.ondragstart = (e) => {
            if (e.target.closest('.card')) { e.preventDefault(); return; }
            dragOperation = 'list';
            draggedList = listEl;
            listEl.classList.add('dragging');
            setTimeout(() => listEl.style.opacity = '0.4', 0);
        };
        listEl.ondragend = () => {
            listEl.classList.remove('dragging');
            listEl.style.opacity = '';
            cleanup();
        };
    }

    function setupCardHandlers(cardEl) {
        const content = cardEl.querySelector('.card-content');
        
        content.onclick = (e) => setupInlineEdit(e.target, 'textarea', (newValue) => {
            for (let list of lists) {
                const card = list.cards.find(c => c.id === cardEl.id);
                if (card) { card.content = newValue; break; }
            }
            content.textContent = newValue;
        });

        cardEl.ondragstart = (e) => {
            e.stopPropagation();
            dragOperation = 'card';
            draggedCard = cardEl;
            cardEl.classList.add('dragging');
            e.dataTransfer.setData('text/plain', cardEl.id);
            setTimeout(() => cardEl.style.opacity = '0.4', 0);
        };
        cardEl.ondragend = () => {
            cardEl.classList.remove('dragging');
            cardEl.style.opacity = '';
            cleanup();
        };
        cardEl.onmouseenter = () => cardEl.style.transform = 'translateY(-2px)';
        cardEl.onmouseleave = () => cardEl.style.transform = '';
    }

    function setupInlineEdit(element, type, onSave) {
        const current = element.textContent.trim();
        const input = document.createElement(type);
        input.value = current;
        input.className = `edit-${type === 'input' ? 'list-title' : 'card-content'}`;
        
        Object.assign(input.style, {
            width: '100%', padding: '4px', border: 'none', 
            boxShadow: '0 0 0 2px #0079bf', borderRadius: '3px'
        });
        
        if (type === 'textarea') {
            input.style.resize = 'vertical';
            input.style.minHeight = '60px';
        }

        element.textContent = '';
        element.appendChild(input);
        input.focus();
        input.select();

        const save = () => {
            const newValue = input.value.trim();
            if (newValue && newValue !== current) onSave(newValue);
            else element.textContent = current;
        };

        input.onblur = save;
        input.onkeydown = (e) => {
            if (e.key === 'Enter' && (type === 'input' || !e.shiftKey)) {
                e.preventDefault();
                save();
            }
        };
    }

    function setupBoardTitleEdit() {
        document.querySelector('.board-header').onclick = function() {
            setupInlineEdit(this, 'input', (newTitle) => {
                this.textContent = newTitle;
                const activeBoard = document.querySelector('.board-item.active span');
                if (activeBoard) activeBoard.textContent = newTitle;
            });
        };
    }

    function setupModalHandlers() {
        const modal = document.getElementById('newBoardModal');
        const nameInput = document.getElementById('newBoardName');
        
        document.querySelector('.add-board').onclick = () => {
            modal.style.display = 'flex';
            nameInput.focus();
        };

        document.getElementById('cancelNewBoard').onclick = () => {
            modal.style.display = 'none';
            nameInput.value = '';
        };

        const createBoard = () => {
            const name = nameInput.value.trim();
            if (name) {
                lists.length = 0;
                hasActiveBoard = true;
                document.querySelector('.board-header').textContent = name;
                
                const newItem = document.createElement('div');
                newItem.className = 'board-item active';
                newItem.innerHTML = `<div class="board-icon"></div><span>${name}</span>`;
                
                document.querySelectorAll('.board-item').forEach(item => item.classList.remove('active'));
                document.querySelector('.add-board').parentNode.insertBefore(newItem, document.querySelector('.add-board'));
                
                newItem.onclick = () => {
                    document.querySelectorAll('.board-item').forEach(item => item.classList.remove('active'));
                    newItem.classList.add('active');
                    document.querySelector('.board-header').textContent = name;
                };

                modal.style.display = 'none';
                nameInput.value = '';
                renderBoard();
            }
        };

        document.getElementById('createNewBoard').onclick = createBoard;
        nameInput.onkeypress = (e) => { if (e.key === 'Enter') createBoard(); };
    }

    function setupDragAndDrop() {
        document.addEventListener('dragover', (e) => {
            e.preventDefault();
            if (dragOperation === 'card') {
                const container = e.target.closest('.list-cards');
                if (container) {
                    container.classList.add('dragover');
                    const afterEl = getDragAfterElement(container, e.clientY, '.card:not(.dragging)');
                    const ghost = document.querySelector('.ghost-card') || document.createElement('div');
                    ghost.className = 'ghost-card';
                    container.insertBefore(ghost, afterEl);
                }
            }
        });

        document.addEventListener('drop', (e) => {
            e.preventDefault();
            const container = e.target.closest('.list-cards');
            if (dragOperation === 'card' && container) {
                const cardId = e.dataTransfer.getData('text/plain');
                const targetListId = container.dataset.listId;
                
                let movedCard = null;
                for (let list of lists) {
                    const cardIdx = list.cards.findIndex(c => c.id === cardId);
                    if (cardIdx !== -1) {
                        movedCard = list.cards.splice(cardIdx, 1)[0];
                        break;
                    }
                }
                
                if (movedCard) {
                    const targetList = lists.find(l => l.id === targetListId);
                    targetList.cards.push(movedCard);
                    renderBoard();
                }
            }
            cleanup();
        });
    }

    function getDragAfterElement(container, coordinate, selector) {
        const elements = [...container.querySelectorAll(selector)];
        return elements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = coordinate - box.top - box.height/2;
            return (offset < 0 && offset > closest.offset) ? {offset, element: child} : closest;
        }, {offset: Number.NEGATIVE_INFINITY}).element;
    }

    function cleanup() {
        document.querySelectorAll('.ghost-card, .dragover, .dragging').forEach(el => {
            el.classList.remove('dragover', 'dragging');
            if (el.classList.contains('ghost-card')) el.remove();
        });
        draggedCard = draggedList = dragOperation = null;
    }

    document.querySelector('.user-logout').onclick = () => window.location.href = 'logout.php';
});