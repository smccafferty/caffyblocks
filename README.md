# CaffyBlocks
CaffyBlocks is a WordPress plugin that provides a foundation for developers, to give publishers a way to easily manage content or settings across a site, as an alternative to bloated page builders. The original intention of this plugin was to curate areas of custom post templates so that the publisher could have finite control of the content displayed, outside of the normal post loop. Such as selecting specific posts or critera used for carousels, providing custom external RSS feeds for different post types and much more.

If I had to sum it up in less that 5 words, its framework for building "blocks." Also, while CaffyBlocks has much of its base functionality, I do want to stress it is still, very much, a work in progress.

### Who is this for?
It's intention is to make life easier for both developers and publishers. It gives a framework for developers to easily add controls and settings, presented in a simple, non bloated way to publishers, so that they can curate the content across their site. I like to refer to it as a map for publishing.

Not only is it easy for developers but I wanted to ensure it was performant. Many times visual editors or page builders are very bloated which makes them VERY slow. While CaffyBlocks is not exactly a visual editor or a page builder, there is not always the need for something as expansive as those options. That is where this plugin comes in, allowing developers to streamline publishers' options, give the publishers exactly what they need to manage the site and caching as much as possible to keep vistors from experiencing long load times!

### What are some ideas where I would use this plugin?
As mentioned in the "Who is this for?" section, think of CaffyBlocks like a map. In the past I have used it to create the wireframes of the post templates. It allows the publisher to use it as a jumping off point to manage each template. In cases where it would be editing existing functionality, the CaffyBlocks admin would link off to the respective editor. Whether it was a specific page, the sidebar or the menu editor.

Generally, I have used this where a site may have 10 to 20 custom post types and the publisher desired granular control of the post types' templates.

### Usage

To simplify the explanation, I have provided a file (example.php) within the plugin that gives an example on how to implement each piece of CaffyBlocks. With the first being the administration portion ( `CaffyBlocks_Admin_Example` ) and the second being the usage portion ( `CaffyBlocks_Usage_Example` ). The following context explains the terminology used for the data structures.

TLDR - There are 4 parts, in the following order from beginning to end.
Foundation
Buildings
Rooms
Accessories

So why the name CaffyBlocks? I wanted to keep the internal data structures relatable to real life. Since many of us used LEGOS as kids, I figure if we think of the design like building a toy house, it would be easier to differentiate each step.

You start with the foundation, in which you would put all of the blocks on. Generally the "foundation" would be the entire site.

Next, you begin creating the buildings on the foundation; could be a house, a garage or a shed. Relating this to CaffyBlocks, this would be each post types' template or specific page templates (homepage, post template, page template, ect).

Then, for each of those buildings you may create some rooms, possibly bedrooms, a kitchen and a living room. Some examples of "rooms" could be different areas of the templates, the header, the footer and the sidebar.

Finally, you have accessories in the rooms like chairs, desks, switches, paintings, ect. This is final element of CaffyBlocks, the meat and potatoes. These are the actual controls for each of the areas, such as labels, checkboxes, select boxes or post selection.

### Installation

After downloading and extracting CaffyBlocks we must run composer to add the dependencies.

```sh
$ cd {site directory}/wp-content/plugins/caffyblocks
$ composer install
```

The compiled CSS is included in the repo although if there is a desire to rebuild the plugin's CSS, a config.rb is already provided to use with Compass. I will note that NPM may need to be installed.

```sh
$ cd {site directory}/wp-content/plugins/caffyblocks
$ npm install
$ compass watch
```

### Todos

Please keep in mind this plugin is very much in its infancy which is why it hasn't been published on wordpress.org, yet. I am still working through the initial development so there is bound to be bugs.

 - Write unit tests!
 - More thorough documentation / examples
 - i18n Support
 - Style the admin template
 - Add additional pre-made "accessories"

License
----
GPLv2
