<div>
    <ul>
        <% loop $Results %>
            <% if $SiteTree %>
                <% with $SiteTree %>
                    <li><a href="$Link">$Title</a></li>
                <% end_with %>
            <% else %>
                <li><a href="$LinkToDataObject">$Title</a></li>
            <% end_if %>

        <% end_loop %>
    </ul>
</div>
