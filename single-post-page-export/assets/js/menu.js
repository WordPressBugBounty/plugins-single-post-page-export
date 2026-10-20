let SS88_SPPE_Menu = {

    close: (wrap, focusToggle)=>{

        let toggle = wrap.querySelector('.sppe-export-toggle');
        let rowActions = wrap.closest('.row-actions');

        wrap.classList.remove('is-open');
        wrap.querySelector('.sppe-export-menu').hidden = true;
        toggle.setAttribute('aria-expanded', 'false');

        // Only remove "visible" if we added it, WordPress uses it too
        if(rowActions && wrap.dataset.sppeVisible) {

            rowActions.classList.remove('visible');
            delete wrap.dataset.sppeVisible;

        }

        if(focusToggle) toggle.focus();

    },
    closeAll: ()=>{

        document.querySelectorAll('.sppe-export.is-open').forEach((wrap) => SS88_SPPE_Menu.close(wrap));

    },
    open: (wrap)=>{

        let rowActions = wrap.closest('.row-actions');

        SS88_SPPE_Menu.closeAll();

        wrap.classList.add('is-open');
        wrap.querySelector('.sppe-export-menu').hidden = false;
        wrap.querySelector('.sppe-export-toggle').setAttribute('aria-expanded', 'true');

        // Keep the row actions on screen when the mouse leaves the row
        if(rowActions && !rowActions.classList.contains('visible')) {

            rowActions.classList.add('visible');
            wrap.dataset.sppeVisible = '1';

        }

        wrap.querySelector('.sppe-export-menu a').focus();

    }

}

document.addEventListener('click', (e) => {

    let toggle = e.target.closest('.sppe-export-toggle');

    if(toggle) {

        e.preventDefault();

        let wrap = toggle.closest('.sppe-export');

        if(wrap.classList.contains('is-open')) SS88_SPPE_Menu.close(wrap);
        else SS88_SPPE_Menu.open(wrap);

        return;

    }

    // Clicking an item starts the download, so close either way
    SS88_SPPE_Menu.closeAll();

});

document.addEventListener('keydown', (e) => {

    let wrap = e.target.closest ? e.target.closest('.sppe-export.is-open') : null;

    if(!wrap) return;

    if('Escape' === e.key) {

        e.preventDefault();
        SS88_SPPE_Menu.close(wrap, true);

    }
    else if('ArrowDown' === e.key || 'ArrowUp' === e.key) {

        let items = Array.from(wrap.querySelectorAll('.sppe-export-menu a'));
        let index = items.indexOf(document.activeElement);

        e.preventDefault();
        index = 'ArrowDown' === e.key ? index + 1 : index - 1;
        items[(index + items.length) % items.length].focus();

    }
    else if('Tab' === e.key) {

        // Tabbing moves past the menu, so close it and let focus move on
        SS88_SPPE_Menu.close(wrap);

    }

});

