# filetree
Drupal 8 port of [Filetree](https://www.drupal.org/project/filetree) module.

## Options

### multi

When enabled, allows more than one folder to be open at the same time. Enabled by default.

`Example: [filetree dir="some-directory" multi="false"]`

### controls

When enabled, adds links to expand and collapse all levels of the tree. Enabled by default. Automatically disabled if "multi" is true.

`Example: [filetree dir="some-directory" controls="false"]`

### absolute

When enabled, formats links to files with absolute URLs. Enabled by default.

`Example: [filetree dir="some-directory" absolute="false"]`

### extensions

When defined, display only filenames without extensions.

`Example: [filetree dir="some-directory" extensions="false"]`

### animation

When enabled, folders will expand and collapse with a fast animation. Set to false if large file trees are causing slow hang-ups. Enabled by default.

`Example: [filetree dir="some-directory" animation="false"]` 

### sortorder

When set to `desc`, files sort in reverse order. `asc` by default.

`Example: [filetree dir="some-directory" sortorder="desc"]`
