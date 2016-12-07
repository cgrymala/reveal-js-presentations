=== Reveal JS Presentations ===
Contributors: cgrymala
Tags: reveal.js, presentation, digital signage, slides
Requires at least: 4.6
Tested up to: 4.7
Stable tag: 1.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows simple creation of Reveal.js presentations in WordPress

== Description ==
Use WordPress to manage your presentations and digital signage displays. Simply create a new presentation, then add your slides to it. This plugin handles most of the rest, automatically combining all of the appropriate slides into an attractive, usable presentation.

= Usage =

Begin by creating a new presentation. Within that presentation, make sure you set all of the appropriate options (see descriptions below). Once you've set up the new presentation, you should create a new Pres. Slide to act as the container for all of your slides within that presentation (making it easier to organize/re-order the slides). Then, create new Pres. Slides for each slide you want in your presentation, assign each of those slides to the appropriate Presentation term, and assign the first parent slide you created as the Parent of that slide.

If you want nested slides (slides you access by pressing the "down" button in your presentation, rather than pressing the "right" button), you can set an individual slide in your presentation as the parent slide.

= Plugin Settings =

The plugin has various settings that are available. To begin with, you can update the global settings/defaults for the plugin itself. To do so, go to Pres. Slides -> Reveal Settings in your admin area. At the moment, that settings page only has one available option, but more will most likely be coming in the future.

1. Add admin menu items for each presentation? - If you want to make it easier to access slides within specific presentations within the admin area, you can enable this option. Once enabled, your Pres. Slides admin menu will have an entry for each individual presentation you've created. Clicking that link will show only the slides that have been assigned to that presentation.

= Presentation Settings =

Next, you can adjust various settings within each individual presentation. When you first create a new presentation, you'll be presented with a handful of options, but more are available once you "Edit" that presentation.

Options marked with an asterisk (*) can be overridden on individual slides. Options marked with a plus sign (+) are available when creating a new presentation; other options listed are only available when editing an existing presentation.

1. Theme+ - which Reveal.js theme you'd like to use for this presentation
1. Transition+* - which transition you want to use in between slides
1. Transition speed+* - how quickly/slowly you want one slide to transition to the next
1. Parallax background image - if you'd like to set up a background image that runs throughout the entire presentation, choose that image here.
1. Custom CSS for this presentation - any CSS that you want applied to the entire presentation (each slide can have its own individual CSS, as well). This CSS will be processed as SASS (SCSS Syntax) and output in the head of the presentation.
1. Display slide controls - whether or not to show the arrows, etc. that allow you to manually control the presentation
1. Display presentation progress bar - whether or not to display a visual indicator showing how far along you are in the presentation
1. Display the page number of the current slide - whether or not to display a numerical representation of which slide you're currently viewing
1. Keep track of slide changes in the address bar - allows you to link directly to an individual slide within the presentation - **If you plan to automatically transition between slides, as you would for a slideshow or digital signage, it is highly recommended that you keep this option disabled**
1. Enable keyboard navigation - whether or not to allow you to navigate between slides using your keyboard
1. Enable slide overview mode - when enabled, you can use the "Esc" key on your keyboard to jump out of one slide and view a series of slides from which to choose
1. Vertically center the slides in the window - whether styles should be applied to keep the content of the slides centered vertically (up and down) in your window
1. Enable touch navigation - whether you should be able to transition between slides by swiping back and forth on a touch interface
1. Loop the presentation - ideal for slideshows/digital signage; not so great for presentations
1. Set up presentation in RTL mode - if you don't know what this setting is, you probably don't need it
1. Turn fragments on - whether or not to support "fragments" of individual slides (portions of a slide that fade-in/roll-in one at a time)
1. Enable mousewheel navigation - whether or not you should be able to transition between slides by simply rolling your mousewheel
1. Hide the address bar on mobile devices - gives you a little more room on the screen if you're viewing the presentation on a mobile device
1. Open links in a popup preview iFrame - if someone clicks a link in your presentation, it will open in a modal-type window, rather than opening a new tab or navigating away from the presentation
1. How long, in milliseconds, should each slide appear on the screen - if you leave this set to 0, the slides can only be advanced manually (ideal for presentations); if you set it to a positive number, slides will automatically advance (ideal for slideshows/digital signage)
1. If the presentation auto-advances, do you want it to stop after user interaction - if someone clicks a link in a slide, or starts to watch a video or something, do you want the slideshow to be paused?
1. Show how many slides on either side of current slide in overview mode - if you have Overview Mode enabled, you can specify how many slides appear on either side when you jump into it (e.g., if you set this to 3, you will see a total of 7 slides in overview mode)

= Digital Signage Options =

These currently do nothing. They will be coming in a future version of the plugin.

= Slide Settings =

There are a number of options available within each individual slide, as well. However, rather than go through just the options, it probably makes more sense to walk you through creating a slide.

= Creating a new slide =

1. Enter a title for the slide, just as you would a post or page.
1. Add the content for the individual slide
1. Choose the appropriate "Presentation" to which this slide belongs. You can choose more than one, but the "parent" option may or may not conflict with that.
1. Parent - if this is a top-level slide, choose your container slide as the parent. If this should be a sub-slide (for which you use the down arrow, rather than right arrow to access the slide), set the appropriate higher-level slide as the parent.
1. Order - use this set the order in which the slides appear within the presentation
1. If you'd like to use a featured image for the slide, go ahead and set it.
1. If you have any notes that you want to display on your screen while running the presentation (they do not appear to people viewing the presentation, just to the presenter when using "Speaker Mode"), enter them in the Speaker Notes area.
1. Use the slide title on the slide? - If you want the Title to appear at the top of the slide, check this box; otherwise, the title will not actually appear anywhere on the slide when viewing the presentation.
1. Use the featured image as the background for this slide? - if you set a featured image for the slide, you can use that featured image as the background image for the entire slide. If you'd prefer to insert an image into the content of the slide (to be contained entirely within the slide), do so using the traditional "Insert Media" method, and leave this option unchecked.
1. Use a custom background color for this slide? - if you want this slide to use a different background color than the rest of the presentation, you can choose that here.
1. Use custom transition settings for this slide? - if you want to use a transition other than the one used for the overall presentation, you can enable this option.
1. Custom CSS for this slide - any CSS entered into this box will be automatically prefixed with the slide's HTML ID, so that it only applies to this individual slide. The CSS in this box is run through a SCSS processor and minified before it's put on the screen, so you can use SCSS syntax within this box. The CSS is also wrapped in the ID of the slide prior to being processed, so any CSS in the box will apply specifically to that slide (because it's processed as SASS, you can simply start entering style definitions directly in the box without any selectors if you want to target the slide content area itself).

== Other Notes ==

If you're going to be creating multiple versions of individual presentations, I highly recommend installing and configuring the [Duplicate Posts](https://wordpress.org/plugins/duplicate-post/) plugin.

In my installation, I have that plugin configured to copy children (which is why I set up a container slide for the presentation, then set all of my actual slides as children of that container), I have it set not to copy the "Presentations" taxonomy (after all, I don't want duplicate slides within a specific presentation), and I have it set not to copy the post status (which then causes all of the posts to be set up as Draft slides, rather than published slides).

Once I duplicate the parent/container slide, all of its children are automatically copied, and the whole set of duplicates are set up as drafts. Then, I create a new Presentation term, set up all of the options for that, then go to my list of draft posts. Once I'm viewing the list of draft posts (which should only contain the duplicates I just created), I bulk edit them and assign the new Presentation appropriately.

= WordPress Features =

Keep in mind that this is all being done within WordPress, so any embeds that are supported within WordPress itself should also be supported within your slides. Likewise, any plugins you install on your WordPress installation should also be usable within your slides.

== Installation ==
1. Upload the `reveal-js-presentations` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Begin creating slides and slideshows

== Frequently Asked Questions ==
= How do I create a presentation? =

Once the plugin is installed, you can begin by creating a new presentation under Slides -> All Presentations. A presentation, in this plugin, is simply a taxonomy to which individual slides can be added.

= How do I add roll-in/fade-in effects for specific parts of a slide? =

Those are known in reveal.js as "fragments". To use them, you will first need to edit the Presentation settings to turn on support for fragments. Then, within your slide, you'll need to actually switch to the Text editor and modify the HTML code. Each segment that's supposed to roll-in or fade-in will need to be wrapped in HTML code with a class of "fragment". For example, you could use the following code:

`
<ul>
	<li class="fragment roll-in">Create a presentation</li>
	<li class="fragment roll-in">Create the parent slide</li>
	<li class="fragment roll-in">Create the child slides</li>
	<li class="fragment roll-in">Enjoy your presentation</li>
</ul>
`

== Changelog ==

= 1.3 =

* Fixes "Hide Admin Bar on Mobile" functionality, h/t [r-a-y](https://github.com/r-a-y)
* Fixes incompatibility with new version of JetPack, h/t [r-a-y](https://github.com/r-a-y)

= 1.1 =

* Begin implementing Facebook Open Graph tags
* Begin implementing Twitter Card tags

= 1.0 =

* Begin implementing various digital signage options