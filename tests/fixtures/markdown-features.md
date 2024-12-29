## Custom Markdown Features
Here are some custom Markdown features Hyde provides!

### Colored Blockquotes

> Normal Blockquote

>info Info Blockquote

>warning Warning Blockquote

>danger Danger Blockquote

>success Success Blockquote

### Automatic file path labels for code blocks

```php
// Filepath: Hello.php
echo 'A file path label has been added to the top right corner.';
```

#### And syntax highlighting

<div><pre><code data-theme="material-theme-palenight" data-lang="php" class="torchlight" style="background-color: #292D3E; --theme-selection-background: #00000080;"><div class="line"><span style="color:#3A3F58; text-align: right; -webkit-user-select: none; user-select: none;" class="line-number">1</span><span style="color: #82AAFF;">echo</span><span style="color: #A6ACCD;"> </span><span style="color: #89DDFF;">'</span><span style="color: #C3E88D;">Syntax highlighted by torchlight.dev.</span><span style="color: #89DDFF;">'</span><span style="color: #89DDFF;">;</span></div></code></pre></div>

---

## Markdown Cheat Sheet

HydePHP uses TailwindCSS prose styles to style the Markdown.
That means it supports cool stuff like [links](#), **bold text**,
*italic text*, ~~strikethrough~~, and `inline code`. And images!
<img src="https://laravel.com/img/logomark.min.svg" alt="Laravel logo" style="float: right">

1. And of course, you can use numbered lists
- Just as you can use unordered ones


## Extended Syntax

These elements extend the basic syntax by adding additional features.

### Table

| Syntax | Description |
| ----------- | ----------- |
| Header | Title |
| Paragraph | Text |


### Task List

- [x] Write the press release
- [ ] Update the website
- [ ] Contact the media
