# ACF Frontend Editor

This is a slightly modified version of Elliot Condon's Live Edit plugin (https://github.com/elliotcondon/live-edit)

This version enables the live editing of individual flexible layouts, as well as their individual fields.

## Editing Flexible Layouts
- To select your layout to edit, first include the name of the flexible content field it belongs to.
- Then to select your layout, include it in the list of editable fields with the prefix 'flex'.
  So if have a flexible content field named "page_sections" and wanted to edit a layout called "main_section",
  the code would look like this: `live_edit( 'page_sections, flex-main_section' );`
  
## Editing Individual Fields within Layouts
- Again, first we would specify the flexible content field
- Next we would add the layout as before, except we will append it with `--` followed by the name of the field.
  So using the previous example, if we wanted to edit a field named 'heading' within the 'main_section' layout,
  the code would like like this: `live_edit( 'page_sections, flex-main_section--heading' );`
