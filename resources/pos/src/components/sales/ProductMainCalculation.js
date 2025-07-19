import React from "react";
import {
    calculateCartTotalAmount,
    calculateSubTotal,
} from "../../shared/calculation/calculation";
import {
    currencySymbolHandling,
    getFormattedMessage,
} from "../../shared/sharedMethod";

const ProductMainCalculation = (props) => {
    const { inputValues, updateProducts, frontSetting, allConfigData } = props;
    
    return (
        <div className="col-xxl-5 col-lg-6 col-md-6 col-12 float-end">
            <div className="card">
                <div className="card-body pt-7 pb-2">
                    <div className="table-responsive">
                        <table className="table border">
                            <tbody>
                                <tr>
                                    <td className="py-3 text-primary">
                                        {getFormattedMessage(
                                            "purchase.grant-total.label"
                                        )}
                                    </td>
                                    <td className="py-3 text-primary">
                                        {currencySymbolHandling(
                                            allConfigData,
                                            frontSetting.value &&
                                                frontSetting.value
                                                    .currency_symbol,
                                            calculateSubTotal(
                                                updateProducts
                                            )
                                        )}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default ProductMainCalculation;
