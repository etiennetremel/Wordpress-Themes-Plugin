<div class="form">
    <p class="shortcode">Use shortcode <kbd>[contact-form name="{{form_name}}"]</kbd></p>
    <table class="form-table">
        <tr>
            <th><label>Name</label></th>
            <td><input name="forms[{{index}}][form_name]" type="text" value="{{form_name}}" class="large-text form-name" /></td>
        </tr>
        <tr>
            <th><label>HTML</label></th>
            <td><textarea name="forms[{{index}}][form_html]" rows="10" cols="50" class="large-text code form-html">{{form_html}}</textarea></td>
        </tr>
        <tr>
            <th>
                <label>Email To</label><br />
                <small>If multiple, separate them with a semicolon</small>
            </th>
            <td><input name="forms[{{index}}][email_to]" type="text" value="{{email_to}}" class="large-text email-to" /></td>
        </tr>
        <tr>
            <th><label>Email Subject</label></th>
            <td><input name="forms[{{index}}][email_subject]" type="text" value="{{email_subject}}" class="large-text email-subject" /></td>
        </tr>
        <tr>
            <th>
                <label>Email Body</label><br />
                <button class="button autogenerate-body">Autogenerate body</button>
            </th>
            <td><textarea name="forms[{{index}}][email_body]" rows="10" cols="50" class="large-text code email-body">{{email_body}}</textarea></td>
        </tr>
        <tr>
            <td></td>
            <td><button class="button delete-form">Delete form</button></td>
        </tr>
    </table>
</div>