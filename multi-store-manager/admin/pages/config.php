<div class="msm-wrap shop-manager">
    <div class="title-bar">
        <h1>Configuration | Multi Store Manager</h1>
    </div>
    <form class="form config show">
        <h3 class="title">Multi Store Configuration | Form</h3>
        <table class="field-wrapper">
            <tbody>
                <tr class="field-group">
                    <td class="label">
                        <label for="map">Google Map API</label>
                    </td>
                    <td>
                        <input type="text" name="map" id="map" placeholder="Paste Google Map API" value="<?= $mapKey ?>" required>
                        <small class="hint">Paste your Google Maps API key here</small>
                    </td>
                </tr>
                <tr class="field-group">
                    <td class="label">
                        <label for="radius">Maximum Radius(km)</label>
                    </td>
                    <td>
                        <input type="text" name="radius" id="radius" placeholder="Enter Value" value="<?= $radius ?>" required>
                        <small class="hint"></small>
                    </td>
                </tr>
            </tbody>
        </table>
        <button class="submit button button-primary">Update Configuration</button>
        <div class="error hidden"></div>
    </form>
</div>
