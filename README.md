# CaffyBlocks
CaffyBlocks is a WordPress plugin that provides a foundation for developers to give publishers a way to easily manage content or setting across a site. The original intention of this plugin was to curate areas of custom post templates so that the publisher could have more finite control of the content outside of the normal post loop displayed. Such as select specific posts or critera used for carousels, providing custom external RSS feeds for different post types.

This plugin is based on a concept I have used in previous projects although felt the need to make it standalone and let others use! I do want to mention it is still very much a work in progress.

### Who is this for?
It's intention to make life easier for both developers and publishers. It gives a framework for developers to easily add controls and settings, presented in a simple, non bloated way to publishers so that they can curate the content across their site. I like to refer to it as a map for publishing.

Not only is it easy for developers but I wanted to ensure it was performant. Many times visual editors or page builders are very bloated which makes them VERY slows. While CaffyBlocks is not exactly a visual editor or a page builder, there is not always the need for something as expansive as those. That is where this plugin comes in, allowing developers to streamline publisher's options, to gives the publishers exactly what they need and caching as much as possible to keep vistors from long load times!

### What are some ideas where I would use this plugin?
As mentions in the "Who is this for?" section, think of CaffyBlocks like a map. In the past I have used it to create the wireframe of the template so that publisher can use it as a jumping off point to manage each template and when existing edit functionality already exists it would link off, such as the Sidebar or Menu editors.

Generally, I have used this where a site may have 10 to 20 custom post types and the publisher desired granular control of the post types' templates.

### Usage
TLDR - There are 4 parts, in the following order from beginning to end.
Foundation
Buildings
Rooms
Accessories

So why the name CaffyBlocks? I wanted to keep the internal data structures relatable to real life. Since many of us used LEGOS as kids, I figure if we think of the design like building a toy house it would be easier to differentiate each step.

You start with the foundation, in which you would put all of the blocks on. Generally the foundation for this plugin would be the entire site.

Next, you begin creating the buildings on the foundation; could be a house, garage, dog house. Many times this would be each post type's template or specific page templates (homepage, contact page, ect).

Then, for each of those buildings you may create some rooms, possibly bedrooms, kitchen and a living room. We could relate this to different areas of the templates; header, footer, sidebar.

Finally, you have accessories in the rooms like chairs, desks, switches, paintings, ect. This is final element, the meat and potatoes. These are the actual controls for each of the areas.

### Installation

After downloading and extracting CaffyBlocks we must run composer to add the dependencies.

```sh
$ cd {site directory}/wp-content/plugins/caffyblocks
$ composer install
```

If there is a desire to rebuild the plugin's CSS, compass is already configured. However, NPM needs to be installed.

```sh
$ npm install
$ compass watch
```

### Todos

Please keep in mind this plugin is very much in its infancy which is why it hasn't been published on wordpress.org, yet. I am still working through the initial development so there is bound to be bugs.

 - Write unit tests!
 - i18n Support
 - Better style the admin template
 - Add additional pre-made "accessories"

License
----
GPLv2