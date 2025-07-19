import { productActionType } from '../../constants';

const initialState = {
    products: [],
    selectedProduct: null,
    allProducts: [],
    productsByWarehouse: [],
    mainProducts: [],
    selectedMainProduct: null,
    lastSalePrices: {}, // Format: { "productId_customerId": price }
};

export default (state = initialState, action) => {
    switch (action.type) {
        case productActionType.FETCH_PRODUCTS:
            return { ...state, products: [...action.payload] };
        case productActionType.FETCH_PRODUCT:
            // Assuming payload is a single product object
            return { ...state, selectedProduct: action.payload };
        case productActionType.ADD_PRODUCT:
            // Assuming payload is the new product, decide how to update 'products' or 'mainProducts'
            // This might need more specific logic based on how ADD_PRODUCT is used.
            // For now, let's assume it might affect 'products' list if it's a general list.
            // Or if it's adding a variant to a main product, selectedMainProduct might need update.
            // Given the original reducer returned action.payload directly, it implies it replaced the state.
            // This needs clarification, but for now, let's assume it's not meant to replace all product state.
            // A safe bet is to add to 'products' or update 'selectedMainProduct' if relevant.
            // The original code `return action.payload` was problematic for a structured state.
            // Let's assume for now it was intended to update a list of products.
            return { ...state, products: [...state.products, action.payload] }; // Or handle based on context
        case productActionType.EDIT_PRODUCT:
            return {
                ...state,
                products: state.products.map(item => item.id === +action.payload.id ? action.payload : item),
                // Also update selectedMainProduct if the edited product is part of it
                selectedMainProduct: state.selectedMainProduct && state.selectedMainProduct.id === action.payload.main_product_id
                    ? {
                        ...state.selectedMainProduct,
                        variations: state.selectedMainProduct.variations.map(v => v.id === +action.payload.id ? action.payload : v)
                    }
                    : state.selectedMainProduct,
            };
        case productActionType.DELETE_PRODUCT:
            // This needs to know which list to filter from, or filter from all relevant lists.
            // Assuming it's from the general 'products' list for now.
            // And also from selectedMainProduct if it's a variation
            return {
                ...state,
                products: state.products.filter(item => item.id !== action.payload),
                selectedMainProduct: state.selectedMainProduct
                    ? {
                        ...state.selectedMainProduct,
                        variations: state.selectedMainProduct.variations.filter(v => v.id !== action.payload)
                    }
                    : null, // or state.selectedMainProduct if deletion is handled specifically for variations elsewhere
            };
        case productActionType.FETCH_ALL_PRODUCTS:
            return { ...state, allProducts: action.payload };
        case productActionType.REMOVE_ALL_PRODUCTS:
            // This action seems to intend to remove a specific product by ID, not all products.
            // The naming is confusing. Assuming it means remove one product.
            return {
                ...state,
                products: state.products.filter(item => item.id !== action.payload),
                allProducts: state.allProducts.filter(item => item.id !== action.payload),
                productsByWarehouse: state.productsByWarehouse.filter(item => item.id !== action.payload),
            };
        case productActionType.FETCH_PRODUCTS_BY_WAREHOUSE:
            return { ...state, productsByWarehouse: action.payload };
        case productActionType.ADD_IMPORT_PRODUCT:
            // Similar to ADD_PRODUCT, the original `return action.payload` is problematic.
            // Assuming this means new products are added to the main 'products' list.
            return { ...state, products: [...state.products, ...action.payload] }; // if payload is an array
        case productActionType.FETCH_ALL_MAIN_PRODUCTS:
            return { ...state, mainProducts: action.payload };
        case productActionType.FETCH_MAIN_PRODUCT:
            return { ...state, selectedMainProduct: action.payload };
        case productActionType.DELETE_MAIN_PRODUCT:
            return {
                ...state,
                mainProducts: state.mainProducts.filter(item => item.id !== action.payload),
                selectedMainProduct: state.selectedMainProduct && state.selectedMainProduct.id === action.payload ? null : state.selectedMainProduct,
            };
        case productActionType.ADD_MAIN_PRODUCT:
             // The original code `return action.payload` was problematic.
             // Assuming it adds to the list of main products.
            return { ...state, mainProducts: [...state.mainProducts, action.payload] };
        case productActionType.EDIT_MAIN_PRODUCT:
            return {
                ...state,
                mainProducts: state.mainProducts.map(item => item.id === +action.payload.id ? action.payload : item),
                selectedMainProduct: state.selectedMainProduct && state.selectedMainProduct.id === +action.payload.id ? action.payload : state.selectedMainProduct,
            };
        case productActionType.FETCH_LAST_SALE_PRICE:
            return {
                ...state,
                lastSalePrices: {
                    ...state.lastSalePrices,
                    [`${action.payload.productId}_${action.payload.customerId}`]: action.payload.price,
                },
            };
        default:
            return state;
    }
};
