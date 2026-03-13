import { HgnTree } from './hgn-tree';

declare global {
    interface Window {
        hgn: HgnTree;
    }
}

window.addEventListener('load', () => {
    window.hgn = HgnTree.getInstance();
    window.hgn.start();
});
