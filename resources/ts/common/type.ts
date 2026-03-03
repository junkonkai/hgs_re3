import { CurrentNode } from "../node/current-node";
import { LinkNode } from "../node/link-node";
import { BasicNode } from "../node/basic-node";
import { LoadMoreNode } from "../node/load-more-node";
import { NodeContent } from "../node/parts/node-content";
import { NodeContentTree } from "../node/parts/node-content-tree";
import { TreeNode } from "../node/tree-node";
import { NodeHead } from "../node/parts/node-head";
import { NodeHeadClickable } from "../node/parts/node-head-clickable";

// 複数のノード型を組み合わせた型エイリアス
export type NodeType = CurrentNode | BasicNode | LinkNode | TreeNode | LoadMoreNode;
export type NodeHeadType = NodeHead | NodeHeadClickable;
export type TreeNodeType = CurrentNode | TreeNode;
export type DisappearRouteNodeType = CurrentNode | LinkNode/* | AccordionTreeNode | ChildTreeLinkNode | ChildTreeNode*/;
export type NodeContentType = NodeContent | NodeContentTree;