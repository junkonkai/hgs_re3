import { CurrentNode } from "../node/current-node";
import { BasicNode } from "../node/basic-node";
import { LoadMoreNode } from "../node/load-more-node";
import { NodeContent } from "../node/parts/node-content";
import { NodeContentTree } from "../node/parts/node-content-tree";
import { TreeNode } from "../node/tree-node";
import { NodeHead } from "../node/parts/node-head";
import { NodeHeadClickable } from "../node/parts/node-head-clickable";

/** Phase6: LinkNode 廃止。link-node は BasicNode で扱う。 */
export type NodeType = CurrentNode | BasicNode | TreeNode | LoadMoreNode;
export type NodeHeadType = NodeHead | NodeHeadClickable;
export type TreeNodeType = CurrentNode | TreeNode;
export type DisappearRouteNodeType = CurrentNode | BasicNode;
export type NodeContentType = NodeContent | NodeContentTree;