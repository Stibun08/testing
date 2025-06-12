<?php
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}
$username = $_SESSION["name"];
$user_initials = strtoupper(substr($username, 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taskmaster</title>
    <style>
        * {box-sizing: border-box; margin: 0; padding: 0; font-family: Arial, sans-serif;}
        body {display: flex; height: 100vh; overflow: hidden;}
        .sidebar {width: 260px; background-color: #f5f5f5; border-right: 1px solid #ddd; display: flex; flex-direction: column; transition: width 0.3s ease; position: relative;}
        .sidebar.collapsed {width: 60px;}
        .sidebar.collapsed .workspace-title,
        .sidebar.collapsed .section-header,
        .sidebar.collapsed .add-board span,
        .sidebar.collapsed .board-item span,
        .sidebar.collapsed .user-name,
        .sidebar.collapsed .user-logout {display: none;}
        .collapse-btn {position: absolute; top: 12px; right: 12px; background: none; border: none; cursor: pointer; color: #97a0af; font-size: 18px; padding: 4px; border-radius: 4px; transition: all 0.2s ease; z-index: 10;}
        .collapse-btn:hover {background-color: #e9e9e9; color: #172b4d;}
        .workspace-header {display: flex; align-items: center; padding: 12px 16px; background-color: #f5f5f5; border-bottom: 1px solid #ddd;}
        .workspace-icon {width: 24px; height: 24px; background-color: #0079bf; border-radius: 4px; margin-right: 8px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;}
        .workspace-title {font-size: 16px; color: #172b4d; font-weight: 800;}
        .sidebar-nav {padding: 12px 0; flex-grow: 1;}
        .section-header {padding: 16px; color: #97a0af; font-size: 12px; font-weight: 600; text-transform: uppercase;}
        .add-board {display: flex; align-items: center; padding: 8px 16px; color: #97a0af; cursor: pointer;}
        .add-board:hover {background-color: #e9e9e9; color: #172b4d;}
        .board-item {display: flex; align-items: center; padding: 8px 16px; color: #172b4d; cursor: pointer;}
        .board-item:hover {background-color: #e9e9e9;}
        .board-item.active {background-color: #e4f0f6;}
        .board-icon {width: 20px; height: 20px; background-color: #0079bf; border-radius: 4px; margin-right: 10px;}
        .nav-icon {margin-right: 10px; display: flex; align-items: center; justify-content: center; width: 20px;}
        .user-section {padding: 12px 16px; border-top: 1px solid #ddd; display: flex; align-items: center;}
        .user-avatar {width: 32px; height: 32px; background-color: #ff8d4e; border-radius: 50%; margin-right: 12px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;}
        .user-info {display: flex; flex-direction: column; flex-grow: 1;}
        .user-name {font-size: 14px; font-weight: 600; color: #172b4d;}
        .user-logout {font-size: 12px; color: #6b778c; cursor: pointer;}
        .main-content {flex-grow: 1; display: flex; flex-direction: column; overflow: hidden;}
        .board-header {padding: 13px 15px; background-color: #1a3f8d; color: white; font-size: 20px; font-weight: bolder; cursor: pointer;}
        #board {flex-grow: 1; display: flex; padding: 20px; overflow-x: auto; background-color: #f9fafc;}
        .welcome-message {position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; color: #172b4d; max-width: 600px; padding: 40px;}
        .welcome-icon {font-size: 4rem; margin-bottom: 24px; opacity: 0.8;}
        .welcome-message h1 {font-size: 3rem; margin-bottom: 20px; color: #0079bf; font-weight: 800; background: linear-gradient(135deg, #0079bf, #005a8b); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;}
        .welcome-message p {font-size: 1.2rem; margin-bottom: 32px; color: #6b778c; line-height: 1.6; font-weight: 400;}
        .create-first-board {background: linear-gradient(135deg, #0079bf, #005a8b); color: white; border: none; padding: 16px 32px; border-radius: 12px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(0, 121, 191, 0.3); text-transform: none;}
        .create-first-board:hover {transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0, 121, 191, 0.4); background: linear-gradient(135deg, #005a8b, #0079bf);}
        .create-first-board:active {transform: translateY(0);}
        .list {width: 300px; background: linear-gradient(145deg, #ffffff, #f8f9fa); border-radius: 16px; margin-right: 20px; display: flex; flex-direction: column; max-height: 100%; flex-shrink: 0; transition: all .3s ease; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); border: 1px solid rgba(255, 255, 255, 0.2); backdrop-filter: blur(10px);}
        .list:hover {transform: translateY(-2px); box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);}
        .list-header {padding: 16px 18px; font-weight: 700; font-size: 15px; color: #2c3e50; border-bottom: 1px solid rgba(0, 0, 0, 0.05); display: flex; align-items: center; justify-content: space-between; background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(248, 249, 250, 0.9)); border-radius: 16px 16px 0 0;}
        .list-title {display: flex; align-items: center;}
        .list-title-text {cursor: pointer; transition: color .2s ease; font-weight: 600;}
        .list-title-text:hover {color: #0079bf;}
        .delete-list {cursor: pointer; color: #95a5a6; font-size: 18px; padding: 6px 8px; visibility: hidden; border-radius: 8px; transition: all .2s ease; font-weight: bold;}
        .list:hover .delete-list {visibility: visible;}
        .delete-list:hover {background: linear-gradient(135deg, #e74c3c, #c0392b); color: white; transform: scale(1.1);}
        .list-color {width: 36px; height: 4px; border-radius: 2px; margin-right: 12px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);}
        .list-cards {padding: 16px; flex-grow: 1; overflow-y: auto; min-height: 20px; background: rgba(255, 255, 255, 0.3);}
        .card {background: linear-gradient(145deg, #ffffff, #f8f9fa); padding: 16px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); margin-bottom: 12px; cursor: pointer; transition: all .3s ease; border: 1px solid rgba(255, 255, 255, 0.6); backdrop-filter: blur(5px); position: relative; overflow: hidden;}
        .card::before {content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent); transform: translateX(-100%); transition: transform 0.6s;}
        .card:hover::before {transform: translateX(100%);}
        .card:hover {transform: translateY(-3px) scale(1.02); box-shadow: 0 8px 25px rgba(0, 121, 191, 0.15); border-color: rgba(0, 121, 191, 0.2);}
        .card-color {width: 100%; height: 4px; border-radius: 2px; margin-bottom: 12px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);}
        .card-content {cursor: pointer; font-weight: 500; color: #2c3e50; line-height: 1.4; position: relative; z-index: 1;}
        .add-card {padding: 12px 18px; color: #7f8c8d; font-size: 14px; cursor: pointer; border-radius: 0 0 16px 16px; transition: all .3s ease; font-weight: 500; background: rgba(255, 255, 255, 0.5); text-align: center;}
        .add-card:hover {background: linear-gradient(135deg, #0079bf, #005a8b); color: white; transform: translateY(-1px);}
        .add-list {background: linear-gradient(145deg, rgba(255, 255, 255, 0.9), rgba(235, 236, 240, 0.9)); border-radius: 16px; width: 300px; padding: 20px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: #34495e; cursor: pointer; height: fit-content; transition: all .3s ease; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); border: 2px dashed rgba(52, 73, 94, 0.3); font-weight: 600;}
        .add-list:hover {background: linear-gradient(135deg, #0079bf, #005a8b); color: white; transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0, 121, 191, 0.2); border-color: transparent;}
        .add-list-icon {margin-right: 10px; font-size: 20px; transition: transform .3s ease;}
        .add-list:hover .add-list-icon {transform: rotate(90deg);}
        .card.dragging, .list.dragging {opacity: .6; transform: scale(1.03); box-shadow: 0 5px 10px rgba(9,30,66,.2); cursor: grabbing; z-index: 100;}
        .list-cards.dragover {background-color: rgba(9,30,66,.08); border-radius: 4px;}
        .ghost-card {height: 80px; background-color: rgba(194,224,255,.5); margin-bottom: 8px; border-radius: 4px; border: 1px dashed #0079bf;}
        .modal {display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;}
        .modal-content {background-color: white; border-radius: 8px; padding: 24px; width: 400px; box-shadow: 0 5px 15px rgba(0,0,0,0.3);}
        .modal h3 {margin-top: 0; color: #172b4d; font-size: 18px;}
        .modal p {color: #6b778c; margin-bottom: 16px;}
        .modal input {width: 100%; padding: 10px; border: 2px solid #dfe1e6; border-radius: 4px; font-size: 14px; margin-bottom: 16px; box-sizing: border-box;}
        .modal-buttons {display: flex; justify-content: flex-end;}
        .modal-cancel {background: none; border: none; color: #6b778c; padding: 8px 16px; cursor: pointer; font-size: 14px; margin-right: 8px;}
        .modal-create {background-color: #0079bf; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-size: 14px;}
    </style>
</head>
<body>
    <div class="sidebar">
        <button class="collapse-btn">☰</button>
        <div class="workspace-header">
            <div class="workspace-icon"><span>T</span></div>
            <div class="workspace-title">Taskmaster</div>
        </div>
        <div class="sidebar-nav">
            <div class="section-header">MY BOARDS</div>
            <div class="add-board"><div class="nav-icon">+</div><span>Create Board</span></div>
        </div>
        <div class="user-section">
            <div class="user-avatar"><?php echo $user_initials; ?></div>
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($username); ?></div>
                <div class="user-logout">Logout</div>
            </div>
        </div>
    </div>
    <div class="main-content">
        <div class="board-header">Welcome to Taskmaster</div>
        <div id="board"></div>
    </div>
    <div id="newBoardModal" class="modal">
        <div class="modal-content">
            <h3>Create New Board</h3>
            <p>Enter a name for your board.</p>
            <input id="newBoardName" type="text" placeholder="Board name">
            <div class="modal-buttons">
                <button id="cancelNewBoard" class="modal-cancel">Cancel</button>
                <button id="createNewBoard" class="modal-create">Create</button>
            </div>
        </div>
    </div>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    let lists = [];
    let draggedCard = null, draggedList = null, dragOperation = null;
    let hasActiveBoard = false;

    // Sidebar collapse functionality
    document.querySelector('.collapse-btn').onclick = () => {
        document.querySelector('.sidebar').classList.toggle('collapsed');
    };

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
    </script>
</body>
</html>