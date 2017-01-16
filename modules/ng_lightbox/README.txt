## Setup

Go to example.com/admin/config/media/ng-lightbox and configure the paths you would
like to be auto-lightboxed.

Anything that runs through theme_link() and mentioned on the admin page is lightboxed, if you
want to manually apply the lightbox to a link, simply add the "ng-lightbox" class to the anchor.

## Rebuilding SASS

If you're contributing to ng_lightbox you can re-generate the css with the
following command from within the ng_lightbox folder.

    compass compile --no-sourcemap --no-debug-info --force -e production sass/lightbox.scss

If you're simply using the ng_lightbox module and want to have your own theme you can copy the
sass file into your theme and begin customising from there.
