
export class NextNodeCache
{
    public title: string;
    public currentNodeTitle: string;
    public currentNodeContent: string;
    public nodes: string;
    public popup: string;
    public url: string;
    public colorState: string;
    public components: { [key: string]: any | null };
    public csrfToken: string;
    public internalNodeHtml?: string;

    public constructor()
    {
        this.title = '';
        this.currentNodeTitle = '';
        this.currentNodeContent = '';
        this.nodes = '';
        this.popup = '';
        this.url = '';
        this.colorState = '';
        this.components = {};
        this.csrfToken = '';
    }
}